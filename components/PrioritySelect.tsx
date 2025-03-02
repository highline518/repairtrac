"use client";

import { Priority } from "@/types/task";
import { cn } from "@/lib/utils";

interface PrioritySelectProps {
  priority: Priority;
  onChange: (priority: Priority) => void;
  className?: string;
}

export default function PrioritySelect({ priority, onChange, className }: PrioritySelectProps) {
  return (
    <div className={cn("flex gap-2", className)}>
      <button
        type="button"
        onClick={() => onChange("no-rush")}
        className={cn(
          "w-6 h-6 rounded-full border-2 transition-all flex items-center justify-center",
          priority === "no-rush"
            ? "border-green-500 bg-green-500"
            : "border-green-500/50 hover:border-green-500"
        )}
      >
        {priority === "no-rush" && (
          <div className="w-3 h-3 rounded-full bg-white" />
        )}
      </button>
      <button
        type="button"
        onClick={() => onChange("important")}
        className={cn(
          "w-6 h-6 rounded-full border-2 transition-all flex items-center justify-center",
          priority === "important"
            ? "border-yellow-500 bg-yellow-500"
            : "border-yellow-500/50 hover:border-yellow-500"
        )}
      >
        {priority === "important" && (
          <div className="w-3 h-3 rounded-full bg-white" />
        )}
      </button>
      <button
        type="button"
        onClick={() => onChange("urgent")}
        className={cn(
          "w-6 h-6 rounded-full border-2 transition-all flex items-center justify-center",
          priority === "urgent"
            ? "border-red-500 bg-red-500"
            : "border-red-500/50 hover:border-red-500"
        )}
      >
        {priority === "urgent" && (
          <div className="w-3 h-3 rounded-full bg-white" />
        )}
      </button>
    </div>
  );
}