"use client";

import { Note } from "@/types/task";
import { cn } from "@/lib/utils";

interface NotesListProps {
  notes: Note[];
  hasIssue: boolean;
}

export default function NotesList({ notes, hasIssue }: NotesListProps) {
  if (notes.length === 0) return null;

  return (
    <div className="mt-4 space-y-2">
      <h4 className={cn(
        "font-semibold",
        hasIssue ? "text-red-100" : "text-card-foreground"
      )}>Notes:</h4>
      {notes.map(note => (
        <div 
          key={note.id}
          className={cn(
            "p-3 rounded-md",
            hasIssue ? "bg-red-950/50" : "bg-secondary"
          )}
        >
          <p className={cn(
            hasIssue ? "text-red-100" : "text-secondary-foreground"
          )}>{note.content}</p>
          <p className="text-sm text-muted-foreground mt-1">{note.timestamp}</p>
        </div>
      ))}
    </div>
  );
}