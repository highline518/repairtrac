"use client";

import { useState } from "react";
import { X } from "lucide-react";
import { cn } from "@/lib/utils";
import { Priority } from "@/types/task";

interface NewTaskDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (roNumber: string, title: string, description: string, priority: Priority) => void;
}

export default function NewTaskDialog({ isOpen, onClose, onSubmit }: NewTaskDialogProps) {
  const [roNumber, setRoNumber] = useState("");
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [priority, setPriority] = useState<Priority>("no-rush");

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(roNumber, title, description, priority);
    setRoNumber("");
    setTitle("");
    setDescription("");
    setPriority("no-rush");
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div className="bg-card w-full max-w-lg rounded-lg shadow-xl p-6 m-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold text-card-foreground">New Task</h2>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="roNumber" className="block text-sm font-medium text-card-foreground mb-1">
              RO Number
            </label>
            <input
              id="roNumber"
              type="text"
              value={roNumber}
              onChange={(e) => setRoNumber(e.target.value)}
              className="w-full p-2 rounded-md bg-background text-foreground border border-border"
              required
            />
          </div>

          <div>
            <label htmlFor="title" className="block text-sm font-medium text-card-foreground mb-1">
              Title
            </label>
            <input
              id="title"
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="w-full p-2 rounded-md bg-background text-foreground border border-border"
              required
            />
          </div>

          <div>
            <label htmlFor="description" className="block text-sm font-medium text-card-foreground mb-1">
              Description / Notes
            </label>
            <textarea
              id="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full p-2 rounded-md bg-background text-foreground border border-border min-h-[100px]"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-card-foreground mb-2">
              Priority Level
            </label>
            <div className="flex gap-4">
              <button
                type="button"
                onClick={() => setPriority("no-rush")}
                className={cn(
                  "flex-1 p-3 rounded-md border-2 transition-all",
                  priority === "no-rush"
                    ? "border-green-500 bg-green-500/10"
                    : "border-border hover:border-green-500/50"
                )}
              >
                <div className="w-3 h-3 rounded-full bg-green-500 mx-auto mb-2" />
                <span className="text-sm font-medium">Non Urgent</span>
              </button>
              <button
                type="button"
                onClick={() => setPriority("important")}
                className={cn(
                  "flex-1 p-3 rounded-md border-2 transition-all",
                  priority === "important"
                    ? "border-yellow-500 bg-yellow-500/10"
                    : "border-border hover:border-yellow-500/50"
                )}
              >
                <div className="w-3 h-3 rounded-full bg-yellow-500 mx-auto mb-2" />
                <span className="text-sm font-medium">Semi Urgent</span>
              </button>
              <button
                type="button"
                onClick={() => setPriority("urgent")}
                className={cn(
                  "flex-1 p-3 rounded-md border-2 transition-all",
                  priority === "urgent"
                    ? "border-red-500 bg-red-500/10"
                    : "border-border hover:border-red-500/50"
                )}
              >
                <div className="w-3 h-3 rounded-full bg-red-500 mx-auto mb-2" />
                <span className="text-sm font-medium">Very Urgent</span>
              </button>
            </div>
          </div>

          <div className="flex justify-end gap-2 mt-6">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 rounded-md bg-yellow-400 text-yellow-950 hover:bg-yellow-300 transition-all shadow-[0_0_15px_rgba(250,204,21,0.5)] hover:shadow-[0_0_25px_rgba(250,204,21,0.7)] font-medium"
            >
              Create Task
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}