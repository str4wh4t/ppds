<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

const roles = usePage().props.roles;

</script>

<template>

    <Head title="Role List" />
    <AuthenticatedLayout>
        <template #header>
            Role List
        </template>
        <div class="ring-1 ring-gray-300 mx-0 rounded-lg mt-4">
            <div class="">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col"
                                class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                Name
                            </th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                GuardName</th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                CreatedAt</th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                UpdatedAt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(role, index) in roles.data" :key="role.id">
                            <td
                                :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                                <div class="font-medium text-gray-900">
                                    {{ role.name }}
                                </div>
                                <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                    <span>{{ role.guard_name }}</span>
                                </div>
                                <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                            </td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                                {{ role.guard_name }}</td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                                {{ $formatDate({ date: role.created_at }) }}</td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-sm font-medium sm:pr-6']">
                                {{ $formatDate({ date: role.updated_at }) }}
                                <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="!roles.prev_page_url ? '#' : roles.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Previous
                    </Link>

                    <Link :href="!roles.next_page_url ? '#' : roles.next_page_url"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Next
                    </Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            {{ ' ' }}
                            <span class="font-medium">{{ roles.from }}</span>
                            {{ ' ' }}
                            to
                            {{ ' ' }}
                            <span class="font-medium">{{ roles.to }}</span>
                            {{ ' ' }}
                            of
                            {{ ' ' }}
                            <span class="font-medium">{{ roles.total }}</span>
                            {{ ' ' }}
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link :href="!roles.prev_page_url ? '#' : roles.prev_page_url"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronLeftIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                            <Link
                                v-for="link in roles.links.filter((link, index) => index !== 0 && index !== roles.links.length - 1)"
                                :key="link.label" :href="!link.url ? '#' : link.url"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 focus:z-20 focus:outline-offset-0"
                                :class="[link.label == roles.current_page ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0']"
                                :disabled="link.label == roles.current_page" as="button" v-html="link.label">
                            </Link>
                            <Link :href="!roles.next_page_url ? '#' : roles.next_page_url"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronRightIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>