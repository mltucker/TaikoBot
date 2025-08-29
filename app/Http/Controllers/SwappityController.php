<?php

namespace App\Http\Controllers;

use App\Enums\LessonParticipationEnum;
use App\Enums\UserRoleEnum;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SwappityController extends Controller
{
    /**
     * Display the teacher assignment matrix.
     */
    public function index(): Response
    {
        Gate::authorize('edit-courses');

        $lessons = Lesson::with(['course:id,name', 'teachers:id,first_name,last_name'])
            ->where('start', '>', Carbon::now()->toDateString())
            ->orderBy('start', 'asc')
            ->get();

        $teachers = User::where('role', UserRoleEnum::TEACHER->value)
            ->orWhere('role', UserRoleEnum::ADMIN->value)
            ->orderBy('first_name', 'asc')
            ->get(['id', 'first_name', 'last_name']);

        // Create matrix data
        $matrix = [];
        foreach ($lessons as $lesson) {
            $lesson->start->inApplicationTz();
            $lesson->finish->inApplicationTz();

            $teacherIds = $lesson->teachers->pluck('id')->toArray();
            $matrix[$lesson->id] = [
                'lesson' => $lesson,
                'teachers' => $teacherIds,
            ];
        }

        return Inertia::render('Swappity/Index', [
            'lessons' => $lessons,
            'teachers' => $teachers,
            'matrix' => $matrix,
        ]);
    }

    /**
     * Update teacher assignments.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('edit-courses');

        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*' => 'array',
            'assignments.*.*' => 'boolean',
        ]);

        foreach ($validated['assignments'] as $lessonId => $teacherAssignments) {
            $lesson = Lesson::findOrFail($lessonId);

            // Get current teachers
            $currentTeachers = $lesson->participants()
                ->wherePivot('participation', LessonParticipationEnum::TEACHER->value)
                ->get();

            // Remove all current teachers
            foreach ($currentTeachers as $currentTeacher) {
                if ($currentTeacher->hasSignedUpToCourse($lesson->course)) {
                    $lesson->participants()->updateExistingPivot($currentTeacher, [
                        'participation' => LessonParticipationEnum::SIGNED_OUT->value,
                    ]);
                } else {
                    $lesson->participants()->detach($currentTeacher);
                }
            }

            // Add new teachers
            foreach ($teacherAssignments as $teacherId => $isAssigned) {
                if ($isAssigned) {
                    $teacher = User::findOrFail($teacherId);

                    if ($teacher->hasSignedInToLesson($lesson)) {
                        $lesson->participants()->updateExistingPivot($teacher, [
                            'participation' => LessonParticipationEnum::TEACHER->value,
                        ]);
                    } else {
                        $lesson->participants()->attach($teacher, [
                            'participation' => LessonParticipationEnum::TEACHER->value,
                        ]);
                    }
                }
            }
        }

        return back()->with('message', 'Teacher assignments updated successfully');
    }
}
