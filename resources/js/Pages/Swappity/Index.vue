<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Box from '@/Components/Box.vue';
import PageContent from '@/Components/PageContent.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    lessons: Array,
    teachers: Array,
    matrix: Object,
});

// Track initial state
const initialState = {};
const currentState = reactive({});

props.lessons.forEach(lesson => {
    const teacherIds = props.matrix[lesson.id].teachers;
    initialState[lesson.id] = [...teacherIds];
    currentState[lesson.id] = {};

    props.teachers.forEach(teacher => {
        currentState[lesson.id][teacher.id] = teacherIds.includes(teacher.id);
    });
});

const form = useForm({
    changes: [],
});

function submit() {
    const changes = [];

    props.lessons.forEach(lesson => {
        const currentTeacherIds = props.teachers
            .filter(teacher => currentState[lesson.id][teacher.id])
            .map(teacher => teacher.id);

        const initialTeacherIds = initialState[lesson.id];

        // Check if this lesson's assignments have changed
        const hasChanged =
            currentTeacherIds.length !== initialTeacherIds.length ||
            !currentTeacherIds.every(id => initialTeacherIds.includes(id)) ||
            !initialTeacherIds.every(id => currentTeacherIds.includes(id));

        if (hasChanged) {
            changes.push({
                lessonId: lesson.id,
                teacherIds: currentTeacherIds,
            });
        }
    });

    form.changes = changes;
    form.put(route('swappity.update'));
}

function goBack() {
    window.history.back();
}

function formatDateTime(start, finish) {
    const startDate = new Date(start);
    const finishDate = new Date(finish);

    const date = startDate.toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: '2-digit',
    });

    const startTime = startDate.toLocaleString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });

    const finishTime = finishDate.toLocaleString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });

    return `${date} ${startTime}-${finishTime}`;
}
</script>

<template>
    <AppLayout title="Swappity">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex flex-row items-center gap-1">
                Swappity - Teacher Assignment Matrix

                <div class="flex-1" />

                <SecondaryButton small @click="goBack">Back</SecondaryButton>
            </h2>
        </template>

        <PageContent>
            <Box>
                <form @submit.prevent="submit">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-left bg-gray-50 dark:bg-gray-700">
                                        Class
                                    </th>
                                    <th v-for="teacher in teachers" :key="teacher.id"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center bg-gray-50 dark:bg-gray-700 min-w-[100px]">
                                        {{ teacher.first_name }}<br>{{ teacher.last_name[0] }}.
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lesson in lessons" :key="lesson.id">
                                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-medium">
                                        <div class="text-sm">
                                            {{ formatDateTime(lesson.start, lesson.finish) }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ lesson.course.name }}
                                        </div>
                                        <div v-if="lesson.title" class="text-xs text-gray-500 dark:text-gray-500">
                                            {{ lesson.title }}
                                        </div>
                                    </td>
                                    <td v-for="teacher in teachers" :key="`${lesson.id}-${teacher.id}`"
                                        class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">
                                        <input
                                            type="checkbox"
                                            v-model="currentState[lesson.id][teacher.id]"
                                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-row items-center gap-2 mt-6">
                        <PrimaryButton type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </PrimaryButton>
                        <SecondaryButton type="button" @click="goBack">
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </Box>
        </PageContent>
    </AppLayout>
</template>
