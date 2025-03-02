"use client";

import { Task, Priority } from "@/types/task";
import { cn } from "@/lib/utils";
import { Check, AlertTriangle, Camera, MessageSquare, Clock, X, GripVertical } from "lucide-react";
import { useState, useRef } from "react";
import NotesList from "./NotesList";
import PhotosList from "./PhotosList";
import PrioritySelect from "./PrioritySelect";
import { Draggable } from 'react-beautiful-dnd';

interface TaskItemProps {
  task: Task;
  index: number;
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
  isActiveNoteTask: boolean;
}

export default function TaskItem({
  task,
  index,
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
  isActiveNoteTask,
}: TaskItemProps) {
  const [noteContent, setNoteContent] = useState("");
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleSubmitNote = () => {
    if (noteContent.trim()) {
      onSubmitNote(task.id, noteContent.trim());
      setNoteContent("");
    }
  };

  const initiatePhotoUpload = () => {
    fileInputRef.current?.click();
  };

  const handlePriorityChange = (priority: Priority) => {
    onUpdatePriority(task.id, priority);
  };

  return (
    <Draggable draggableId={task.id.toString()} index={index}>
      {(provided, snapshot) => (
        <div
          ref={provided.innerRef}
          {...provided.draggableProps}
          className={cn(
            "w-full transition-shadow mb-4",
            snapshot.isDragging && "shadow-2xl"
          )}
        >
          <div className={cn(
            "rounded-lg shadow-lg p-4 transition-colors",
            task.hasIssue ? "bg-red-900/90 text-white" : 
            task.completed ? "bg-green-900/90 text-white" :
            task.inProgress ? "bg-blue-900/20" : "bg-card"
          )}>
            <div className="flex gap-4">
              {/* Drag Handle */}
              <div 
                {...provided.dragHandleProps}
                className={cn(
                  "flex items-center text-muted-foreground hover:text-foreground transition-colors cursor-grab active:cursor-grabbing",
                  task.hasIssue && "text-red-200 hover:text-white",
                  task.completed && "text-green-200 hover:text-white"
                )}
              >
                <GripVertical className="w-5 h-5" />
              </div>

              {/* Priority Indicators */}
              <div className="flex flex-col items-center gap-2 mt-1">
                <div 
                  className={cn(
                    "w-6 h-6 rounded-full border-2 flex items-center justify-center",
                    task.priority === "no-rush" ? "border-green-500 bg-green-500" : "border-green-500/30",
                    task.hasIssue && "opacity-70"
                  )}
                >
                  {task.priority === "no-rush" && (
                    <div className="w-3 h-3 rounded-full bg-white" />
                  )}
                </div>
                <div 
                  className={cn(
                    "w-6 h-6 rounded-full border-2 flex items-center justify-center",
                    task.priority === "important" ? "border-yellow-500 bg-yellow-500" : "border-yellow-500/30",
                    task.hasIssue && "opacity-70"
                  )}
                >
                  {task.priority === "important" && (
                    <div className="w-3 h-3 rounded-full bg-white" />
                  )}
                </div>
                <div 
                  className={cn(
                    "w-6 h-6 rounded-full border-2 flex items-center justify-center",
                    task.priority === "urgent" ? "border-red-500 bg-red-500" : "border-red-500/30",
                    task.hasIssue && "opacity-70"
                  )}
                >
                  {task.priority === "urgent" && (
                    <div className="w-3 h-3 rounded-full bg-white" />
                  )}
                </div>
              </div>

              {/* Main Content */}
              <div className="flex-1">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div
                      className="cursor-pointer"
                      onClick={() => onToggleExpand(task.id)}
                    >
                      <h3 className={cn(
                        "font-semibold text-lg",
                        task.hasIssue || task.completed ? "text-white" : "text-card-foreground"
                      )}>{task.title}</h3>
                      <p className="text-sm text-muted-foreground">RO #{task.roNumber}</p>
                    </div>
                    {task.inProgress && (
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-200 mt-2">
                        <Clock className="w-3 h-3 mr-1" />
                        In Progress
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => onDeleteTask(task.id)}
                      className="text-red-500 hover:text-red-600 transition-colors"
                    >
                      <X className="w-5 h-5" />
                    </button>
                    <button 
                      onClick={() => onToggleExpand(task.id)}
                      className={cn(
                        "transition-colors",
                        task.hasIssue ? "text-red-200 hover:text-white" : "text-muted-foreground hover:text-foreground"
                      )}
                    >
                      <svg
                        className={cn(
                          "w-6 h-6 transform transition-transform",
                          task.expanded ? "rotate-180" : ""
                        )}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M19 9l-7 7-7-7"
                        />
                      </svg>
                    </button>
                  </div>
                </div>

                {task.expanded && (
                  <div onClick={(e) => e.stopPropagation()}>
                    <p className={cn(
                      "mt-2",
                      task.hasIssue || task.completed ? "text-gray-100" : "text-muted-foreground"
                    )}>{task.description}</p>

                    <NotesList notes={task.notes} hasIssue={task.hasIssue} />
                    <PhotosList 
                      photos={task.photos} 
                      hasIssue={task.hasIssue} 
                      onDeletePhoto={(photoId) => onDeletePhoto(task.id, photoId)}
                    />

                    {/* Add Note Form */}
                    {isActiveNoteTask && (
                      <div className="mt-4">
                        <textarea
                          value={noteContent}
                          onChange={(e) => setNoteContent(e.target.value)}
                          placeholder="Type your note here..."
                          className="w-full p-2 rounded-md bg-background text-foreground border border-border min-h-[100px]"
                        />
                        <div className="flex gap-2 mt-2">
                          <button
                            onClick={handleSubmitNote}
                            className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                          >
                            Submit
                          </button>
                          <button
                            onClick={() => onAddNote("")}
                            className="bg-secondary text-secondary-foreground px-4 py-2 rounded-md hover:bg-secondary/80 transition-colors"
                          >
                            Cancel
                          </button>
                        </div>
                      </div>
                    )}

                    <div className="mt-4 flex flex-wrap gap-2">
                      <button 
                        className="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                        onClick={() => onToggleComplete(task.id)}
                      >
                        <Check className="w-4 h-4" />
                        {task.completed ? "Not Complete" : "Complete"}
                      </button>
                      <button 
                        onClick={() => onToggleIssue(task.id)}
                        className={cn(
                          "flex items-center gap-2 px-4 py-2 rounded-md transition-colors",
                          task.hasIssue 
                            ? "bg-yellow-500 text-yellow-950 hover:bg-yellow-400"
                            : "bg-red-600 text-white hover:bg-red-700"
                        )}
                      >
                        <AlertTriangle className="w-4 h-4" />
                        {task.hasIssue ? "Resolve Issue" : "Issue"}
                      </button>
                      <button 
                        onClick={() => onToggleInProgress(task.id)}
                        className={cn(
                          "flex items-center gap-2 px-4 py-2 rounded-md transition-colors",
                          task.inProgress
                            ? "bg-blue-400 text-blue-950 hover:bg-blue-300"
                            : "bg-blue-600 text-white hover:bg-blue-700"
                        )}
                      >
                        <Clock className="w-4 h-4" />
                        {task.inProgress ? "Pause Work" : "Start Work"}
                      </button>
                      <label className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors cursor-pointer">
                        <Camera className="w-4 h-4" />
                        <span>Add Photos</span>
                        <input
                          type="file"
                          accept="image/*"
                          multiple
                          className="hidden"
                          onChange={(e) => onAddPhoto(task.id, e)}
                        />
                      </label>
                      <button 
                        onClick={() => onAddNote(task.id)}
                        className="flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors"
                      >
                        <MessageSquare className="w-4 h-4" />
                        Add Notes
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </Draggable>
  );
}