<script setup>
defineProps({
});
</script>

<template>
    <div class="flex flex-col items-stretch gap-2 p-2 md:p-4 bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
        <slot />
    </div>
</template>
