<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head, usePage, router } from '@inertiajs/vue3';
import { ref, defineProps, onMounted, computed } from "vue";
import { use } from "echarts/core";
import { BarChart, PieChart } from "echarts/charts";
import { CanvasRenderer } from "echarts/renderers";
import { TooltipComponent, LegendComponent, TitleComponent, GridComponent } from "echarts/components";
import VChart from "vue-echarts";

use([CanvasRenderer, BarChart, PieChart, TooltipComponent, LegendComponent, TitleComponent, GridComponent]);

// Menerima props dari Laravel Breeze (Inertia)
const props = defineProps({
  barData: Array,
  pieData: Array,
  tableData: Array
});

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

// const tableData = computed(() => usePage().props.tableData);

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
</script>


<template>
    <Head title="Statistic" />
    <AuthenticatedLayout>
        <template #header>
           Statistic
        </template>
        <div class="lg:flex lg:h-full lg:flex-col mt-4">
            <div class="chart-container">
                <!-- Chart dengan bingkai -->
                <div class="chart-box">
                    <VChart :option="barOptions" class="chart-content-bar" />
                </div>
                <div class="chart-box">
                    <VChart :option="pieOptions" class="chart-content-pie" />
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
                                {{ unit.not_monitored_users }}</td>
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