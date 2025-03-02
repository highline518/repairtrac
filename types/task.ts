export type Priority = "urgent" | "important" | "no-rush" | "done";

export interface Note {
  id: string;
  content: string;
  timestamp: string;
}

export interface Photo {
  id: string;
  url: string;
  thumbnail: string;
}

export interface Task {
  id: string;
  roNumber: string;
  title: string;
  description: string;
  priority: Priority;
  expanded: boolean;
  hasIssue: boolean;
  inProgress: boolean;
  completed: boolean;
  notes: Note[];
  photos: Photo[];
  position: number;
}