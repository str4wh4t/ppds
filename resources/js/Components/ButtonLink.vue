<template>
    <a :href="href" :target="target" :rel="rel"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </a>
</template>

<script>
export default {
    name: "ButtonLink",
    props: {
        href: {
            type: String,
            required: true,
        },
        target: {
            type: String,
            default: "_self", // Default target adalah tab yang sama
        },
        rel: {
            type: String,
            default: "noopener noreferrer", // Rel default untuk keamanan (misalnya pada target="_blank")
        },
    },
};
</script>