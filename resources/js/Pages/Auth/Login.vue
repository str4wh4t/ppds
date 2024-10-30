<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const isLupaPassword = ref(false);

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

async function generateToken() {
    try {
        const response = await axios.post('/api/generate-token');
        const token = response.data.token;

        // Simpan token di localStorage atau tempat lain yang aman
        localStorage.setItem('token', token);

        return token;
    } catch (error) {
        console.error("Token generation failed:", error.response ? error.response.data : error.message);
        return null;
    }
}

const submit = () => {
    form.post(route('login'), {
        onFinish: (data) => {
            form.reset('password')
            // generateToken();
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="username" value="Username" />

                <TextInput id="username" type="text" class="mt-1 block w-full" v-model="form.username" required
                    autofocus autocomplete="username" />

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                    autocomplete="current-password" />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div> -->

            <div class="flex items-center justify-end mt-10">
                <!-- <Link v-if="canResetPassword" :href="route('password.request')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Forgot your password?
                </Link> -->

                <a href="#" @click="isLupaPassword = true"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Lupa password?
                </a>
                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
    <Modal :show="isLupaPassword" @close="isLupaPassword = false">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Perhatian
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Untuk lupa password silahkan dapat menghubungi admin.
            </p>
        </div>
    </Modal>

</template>
