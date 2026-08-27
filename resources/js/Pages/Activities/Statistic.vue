<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from "vue";
import { use } from "echarts/core";
import { BarChart, PieChart } from "echarts/charts";
import { CanvasRenderer } from "echarts/renderers";
import { TooltipComponent, LegendComponent, TitleComponent, GridComponent } from "echarts/components";
import VChart from "vue-echarts";
import moment from 'moment';
import SelectInput from '@/Components/SelectInputBasic.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

use([CanvasRenderer, BarChart, PieChart, TooltipComponent, LegendComponent, TitleComponent, GridComponent]);

// Menerima props dari Laravel Breeze (Inertia)
const props = defineProps({
    barData: Array,
    pieData: Array,
    tableData: Array,
    weekMonitorStats: Array,
    pieChartData: Array
});

const startYear = 2024;
const endYear = 2030;

const yearList = Array.from({ length: endYear - startYear + 1 }, (_, i) => ({
    id: i + 1,
    name: String(startYear + i),
}));

const yearSelected = ref(usePage().props.filters.yearSelected);
const yearSelectedOpt = ref(yearList.find(year => year.name == yearSelected.value) || null);

const monthList = moment.months().map((month, index) => ({
    id: index,
    name: month
}));

const monthIndexSelected = ref(usePage().props.filters.monthIndexSelected);
const monthSelectedOpt = ref(monthList.find(month => month.id == monthIndexSelected.value) || null);

const weekIndexList = [
    { id: 1, name: 'Minggu-1' },
    { id: 2, name: 'Minggu-2' },
    { id: 3, name: 'Minggu-3' },
    { id: 4, name: 'Minggu-4' },
    { id: 5, name: 'Minggu-5' },
];

const weekIndexSelected = ref(usePage().props.filters.weekIndexSelected);
const weekIndexSelectedOpt = ref(weekIndexList.find(weekIndex => weekIndex.id == weekIndexSelected.value) || null);

const showNotMonitoredModal = ref(false);
const notMonitoredLoading = ref(false);
const notMonitoredError = ref('');
const notMonitoredUnitName = ref('');
const notMonitoredStudents = ref([]);
const detailModalTitle = ref('Mahasiswa Belum Mengisi');
const detailShowWorkload = ref(false);

const statisticFilterParams = () => ({
    yearSelected: yearSelected.value || null,
    monthIndexSelected: monthIndexSelected.value != null ? monthIndexSelected.value + 1 : null,
    weekIndexSelected: weekIndexSelected.value || null,
});

const openNotMonitoredModal = async (unit) => {
    if (!unit?.id || !unit?.not_monitored_users) {
        return;
    }

    showNotMonitoredModal.value = true;
    notMonitoredLoading.value = true;
    notMonitoredError.value = '';
    notMonitoredUnitName.value = unit.name;
    notMonitoredStudents.value = [];
    detailModalTitle.value = 'Mahasiswa Belum Mengisi';
    detailShowWorkload.value = false;

    try {
        const response = await axios.get(
            route('activities.statistic.not-monitored', {
                user: usePage().props.auth.user,
                unit: unit.id,
            }),
            { params: statisticFilterParams() }
        );
        notMonitoredStudents.value = response.data?.data ?? [];
        notMonitoredUnitName.value = response.data?.unit?.name ?? unit.name;
    } catch (error) {
        notMonitoredError.value = error.response?.data?.message || 'Gagal memuat daftar mahasiswa.';
    } finally {
        notMonitoredLoading.value = false;
    }
};

const openWorkloadModal = async (unit, category, count) => {
    if (!unit?.id || !count) {
        return;
    }

    const titles = {
        '71_80': 'Mahasiswa Beban Kerja 71 - 80 Jam',
        above_80: 'Mahasiswa Beban Kerja Lebih 80 Jam',
    };

    showNotMonitoredModal.value = true;
    notMonitoredLoading.value = true;
    notMonitoredError.value = '';
    notMonitoredUnitName.value = unit.name;
    notMonitoredStudents.value = [];
    detailModalTitle.value = titles[category] || 'Mahasiswa Beban Kerja';
    detailShowWorkload.value = true;

    try {
        const response = await axios.get(
            route('activities.statistic.workload-students', {
                user: usePage().props.auth.user,
                unit: unit.id,
            }),
            {
                params: {
                    ...statisticFilterParams(),
                    category,
                },
            }
        );
        notMonitoredStudents.value = response.data?.data ?? [];
        notMonitoredUnitName.value = response.data?.unit?.name ?? unit.name;
        if (response.data?.category_label) {
            detailModalTitle.value = `Mahasiswa Beban Kerja ${response.data.category_label}`;
        }
    } catch (error) {
        notMonitoredError.value = error.response?.data?.message || 'Gagal memuat daftar mahasiswa.';
    } finally {
        notMonitoredLoading.value = false;
    }
};

const closeNotMonitoredModal = () => {
    showNotMonitoredModal.value = false;
    notMonitoredError.value = '';
    notMonitoredStudents.value = [];
    detailShowWorkload.value = false;
};

// Data untuk Bar Chart (Persentase)
const barOptions = ref({
  title: { text: "Persentase Mhs Mengisi", left: "center" },
  tooltip: { 
    trigger: "item",
    formatter: (params) => `${params.seriesName}<br/> ● ${params.name}: <b>${params.value}%</b>`
 },
  xAxis: { type: "value", max: 100, axisLabel: { formatter: "{value}%" } },
  yAxis: {
    type: "category",
    data: props.barData.map(unit => unit.name),
    axisLabel: {
      rotate: 60, // Memiringkan label Y-Axis 30 derajat
      fontSize: 12, // Ukuran font agar lebih jelas
      overflow: "truncate", // Menghindari teks terlalu panjang
      width: 100 // Menyesuaikan lebar maksimum label
    }
  },
  series: [
    {
      name: "Persentase",
      type: "bar",
      data: props.barData.map(unit => unit.value),
      itemStyle: { color: "#4169E1" }
    }
  ]
});

// Data untuk Pie Chart
const pieOptions = ref({
    title: { text: "Jumlah Mhs Mengisi", left: "center" },
    tooltip: { trigger: "item" },
    series: [
        {
            name: "Jumlah",
            type: "pie",
            radius: "75%",
            data: props.pieData
        }
    ]
});

// Hitung Total di tfoot
const totalUsers = computed(() =>
  props.tableData.reduce((sum, unit) => sum + unit.total_users, 0)
);

const totalMonitoredUsers = computed(() =>
  props.tableData.reduce((sum, unit) => sum + unit.monitored_users, 0)
);

const totalNotMonitoredUsers = computed(() =>
  props.tableData.reduce((sum, unit) => sum + unit.not_monitored_users, 0)
);

const averagePercentage = computed(() => {
  const totalPercentage = props.tableData.reduce((sum, unit) => sum + unit.percentage, 0);
  return props.tableData.length > 0 ? (totalPercentage / props.tableData.length).toFixed(2) : "0.00";
});

watch(
  () => yearSelectedOpt.value, // Amati perubahan `yearSelectedOpt.value`
  (newYear, oldYear) => {
      router.get(
        route("activities.statistic", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          yearSelected: newYear?.name || null,
          monthIndexSelected: monthIndexSelected.value + 1  || null,
          weekIndexSelected: weekIndexSelected.value || null
        },
        { replace: true }
      );
  },
);

watch(
  () => monthSelectedOpt.value, // Amati perubahan `monthSelectedOpt.value`
  (newMonth, oldMonth) => {
      router.get(
        route("activities.statistic", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          yearSelected: yearSelected.value || null,
          monthIndexSelected: newMonth?.id + 1 || null,
          weekIndexSelected: weekIndexSelected.value || null
        },
        { replace: true }
      );
  },
);

watch(
  () => weekIndexSelectedOpt.value, // Amati perubahan `monthSelectedOpt.value`
  (newWeekIndex, oldWeekIndex) => {
      router.get(
        route("activities.statistic", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          yearSelected: yearSelected.value || null,
          monthIndexSelected: monthIndexSelected.value + 1 || null,
          weekIndexSelected: newWeekIndex?.id || null
        },
        { replace: true }
      );
  },
);

// Konfigurasi Pie Chart untuk Workload Hours
const workloadPieOptions = ref({
  title: { text: "Distribusi Beban Kerja", left: "center" },
  tooltip: { trigger: "item" },
  series: [
    {
      name: "Workload Hours",
      type: "pie",
      radius: "75%",
      data: props.pieChartData
    }
  ]
});
</script>


<template>
    <Head title="Statistic" />
    <AuthenticatedLayout>
        <template #header>
           Statistic
        </template>
        <div class="flex justify-center items-center mt-4 w-full" v-if="!$hasRoles('student')">
            <div class="w-48">
                <SelectInput id="yearSelected" class="block w-full" v-model="yearSelectedOpt" :options="yearList"
                    required autofocus autocomplete="yearSelected" placeholder="Select year" />
            </div>
            <div class="w-48 ml-4">
                <SelectInput id="monthSelected" class="block w-full" v-model="monthSelectedOpt" :options="monthList"
                    required autofocus autocomplete="monthSelected" placeholder="Select month" />
            </div>
            <div class="w-48 ml-4">
                <SelectInput id="weekIndexSelected" class="block w-full" v-model="weekIndexSelectedOpt" :options="weekIndexList"
                    required autofocus autocomplete="weekIndexSelected" placeholder="Select week index" />
            </div>
        </div>
        <div class="lg:flex lg:h-full lg:flex-col mt-4">
            <div class="chart-container">
                <!-- Chart dengan bingkai -->
                <div class="chart-box">
                    <VChart :option="barOptions" class="chart-content-bar" />
                </div>
                <div class="chart-box">
                    <VChart :option="pieOptions" class="chart-content-pie" />
                </div>
                <div class="chart-box">
                    <VChart :option="workloadPieOptions" class="chart-content-pie" />
                </div>
            </div>
        </div>
        <div class="mt-4 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <div class="overflow-x-auto w-full">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col"
                                class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                Prodi
                            </th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Jml Mhs
                            </th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Mengisi
                            </th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Belum
                            </th>
                            <th scope="col"
                                class="relative px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Jml Mhs (%)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop untuk membuat row berdasarkan tableData -->
                        <tr v-if="$hasItems(tableData)" v-for="(unit, index) in tableData"
                            :key="unit.name">
                            <td
                                :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                                <div class="font-medium text-gray-900 whitespace-nowrap">
                                    {{ unit.name }}
                                </div>
                                <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                    <span class="block">{{ unit.total_users }}</span>
                                </div>
                                <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                            </td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                {{ unit.total_users }}</td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                {{ unit.monitored_users }}</td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                <button
                                    v-if="unit.not_monitored_users > 0"
                                    type="button"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                                    @click="openNotMonitoredModal(unit)"
                                >
                                    {{ unit.not_monitored_users }}
                                </button>
                                <span v-else>{{ unit.not_monitored_users }}</span>
                            </td>
                            <td :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-sm text-center font-medium sm:pr-6']">
                                {{ unit.percentage }}
                                <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                            </td>
                        </tr>
                        <tr v-else>
                            <td :colspan="5" class="h-10 text-center font-bold text-indigo-600">Empty
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Total</td>
                            <td class="hidden px-3 py-2 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 lg:table-cell">{{ totalUsers }}</td>
                            <td class="hidden px-3 py-2 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 lg:table-cell">{{ totalMonitoredUsers }}</td>
                            <td class="hidden px-3 py-2 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 lg:table-cell">{{ totalNotMonitoredUsers }}</td>
                            <td class="py-2 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 lg:table-cell">{{ averagePercentage }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="mt-4 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <div class="overflow-x-auto w-full">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col"
                                class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                Prodi
                            </th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Kurang 71 Jam
                            </th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                71 - 80 Jam
                            </th>
                            <th scope="col"
                                class="relative px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Lebih 80 Jam
                            </th>
                            <th scope="col"
                                class="relative px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                                Total Data
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop untuk membuat row berdasarkan weekMonitorStats -->
                        <tr v-if="$hasItems(weekMonitorStats)" v-for="(unit, index) in weekMonitorStats"
                            :key="unit.name">
                            <td
                                :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                                <div class="font-medium text-gray-900 whitespace-nowrap">
                                    {{ unit.name }}
                                </div>
                                <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                    <span class="block">{{ unit.workload_below_71 }}</span>
                                </div>
                                <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                            </td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                {{ unit.workload_below_71 }}</td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                <button
                                    v-if="Number(unit.workload_71_to_80) > 0"
                                    type="button"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                                    @click="openWorkloadModal(unit, '71_80', Number(unit.workload_71_to_80))"
                                >
                                    {{ unit.workload_71_to_80 }}
                                </button>
                                <span v-else>{{ unit.workload_71_to_80 }}</span>
                            </td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                <button
                                    v-if="Number(unit.workload_above_80) > 0"
                                    type="button"
                                    class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                                    @click="openWorkloadModal(unit, 'above_80', Number(unit.workload_above_80))"
                                >
                                    {{ unit.workload_above_80 }}
                                </button>
                                <span v-else>{{ unit.workload_above_80 }}</span>
                            </td>
                            <td :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-sm text-center font-medium sm:pr-6']">
                                {{ unit.total_monitored_users }}
                                <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                            </td>
                        </tr>
                        <tr v-else>
                            <td :colspan="5" class="h-10 text-center font-bold text-indigo-600">Empty
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showNotMonitoredModal" max-width="4xl" @close="closeNotMonitoredModal">
            <div class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ detailModalTitle }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Prodi: <span class="font-semibold text-gray-900">{{ notMonitoredUnitName }}</span>
                    </p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div v-if="notMonitoredLoading" class="py-8 text-center text-sm text-gray-500">
                        Memuat daftar mahasiswa...
                    </div>
                    <div v-else-if="notMonitoredError" class="py-4 text-center text-sm text-red-600">
                        {{ notMonitoredError }}
                    </div>
                    <div v-else class="overflow-x-auto ring-1 ring-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">No</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Nama</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Username</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Identity</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Semester</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Email</th>
                                    <th v-if="detailShowWorkload" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Workload</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="(student, idx) in notMonitoredStudents" :key="student.id">
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-gray-900">{{ student.fullname }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600">{{ student.username }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600">{{ student.identity }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600">{{ student.semester ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600">{{ student.email }}</td>
                                    <td v-if="detailShowWorkload" class="whitespace-nowrap px-4 py-2 text-sm text-gray-600">
                                        {{ student.workload ?? student.workload_hours ?? '-' }}
                                    </td>
                                </tr>
                                <tr v-if="!notMonitoredStudents.length">
                                    <td :colspan="detailShowWorkload ? 7 : 6" class="px-4 py-6 text-center text-sm font-medium text-indigo-600">
                                        Tidak ada mahasiswa
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <SecondaryButton type="button" @click="closeNotMonitoredModal">
                            Tutup
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.chart-container {
  display: flex;
  flex-direction: column; /* Mengatur grafik secara vertikal */
  align-items: center; /* Tengah secara horizontal */
  gap: 20px; /* Jarak antara grafik */
}

.chart-box {
  @apply border border-gray-300 shadow-lg rounded-lg p-4 bg-white w-full; /* Tailwind classes */
}

.chart-content-bar {
  @apply w-full h-[1000px]; /* Mengatur tinggi chart */
}

.chart-content-pie {
  @apply w-full h-[600px]; /* Mengatur tinggi chart */
}
</style>
