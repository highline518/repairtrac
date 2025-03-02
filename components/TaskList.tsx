"use client";

import { Task, Priority } from "@/types/task";
import TaskItem from "./TaskItem";
import { Droppable, DroppableProps } from 'react-beautiful-dnd';
import { useEffect, useState } from 'react';

interface TaskListProps {
  tasks: Task[];
  onToggleExpand: (id: string) => void;
  onToggleIssue: (id: string) => void;
  onToggleInProgress: (id: string) => void;
  onToggleComplete: (id: string) => void;
  onDeleteTask: (id: string) => void;
  onDeletePhoto: (taskId: string, photoId: string) => void;
  onAddNote: (id: string) => void;
  onSubmitNote: (id: string, content: string) => void;
  onAddPhoto: (id: string, event: React.ChangeEvent<HTMLInputElement>) => void;
  onUpdatePriority: (id: string, priority: Priority) => void;
  activeNoteTask: string | null;
}

// Strict mode wrapper for Droppable that handles hydration issues
function StrictModeDroppable({ children, ...props }: DroppableProps) {
  const [enabled, setEnabled] = useState(false);

  useEffect(() => {
    const animation = requestAnimationFrame(() => setEnabled(true));
    return () => {
      cancelAnimationFrame(animation);
      setEnabled(false);
    };
  }, []);

  if (!enabled) {
    return null;
  }

  return <Droppable {...props}>{children}</Droppable>;
}

export default function TaskList({
  tasks,
  onToggleExpand,
  onToggleIssue,
  onToggleInProgress,
  onToggleComplete,
  onDeleteTask,
  onDeletePhoto,
  onAddNote,
  onSubmitNote,
  onAddPhoto,
  onUpdatePriority,
  activeNoteTask,
}: TaskListProps) {
  return (
    <StrictModeDroppable droppableId="tasks">
      {(provided) => (
        <div 
          className="flex flex-col gap-4"
          {...provided.droppableProps}
          ref={provided.innerRef}
        >
          {tasks.map((task, index) => (
            <TaskItem
              key={task.id}
              task={task}
              index={index}
              onToggleExpand={onToggleExpand}
              onToggleIssue={onToggleIssue}
              onToggleInProgress={onToggleInProgress}
              onToggleComplete={onToggleComplete}
              onDeleteTask={onDeleteTask}
              onDeletePhoto={onDeletePhoto}
              onAddNote={onAddNote}
              onSubmitNote={onSubmitNote}
              onAddPhoto={onAddPhoto}
              onUpdatePriority={onUpdatePriority}
              isActiveNoteTask={activeNoteTask === task.id}
            />
          ))}
          {provided.placeholder}
        </div>
      )}
    </StrictModeDroppable>
  );
}