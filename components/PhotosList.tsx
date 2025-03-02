"use client";

import { Photo } from "@/types/task";
import { cn } from "@/lib/utils";
import { X } from "lucide-react";

interface PhotosListProps {
  photos: Photo[];
  hasIssue: boolean;
  onDeletePhoto: (photoId: string) => void;
}

export default function PhotosList({ photos, hasIssue, onDeletePhoto }: PhotosListProps) {
  if (photos.length === 0) return null;

  return (
    <div className="mt-4">
      <h4 className={cn(
        "font-semibold mb-2",
        hasIssue ? "text-red-100" : "text-card-foreground"
      )}>Photos:</h4>
      <div className="flex flex-wrap gap-2">
        {photos.map(photo => (
          <div key={photo.id} className="relative group">
            <a
              href={photo.url}
              target="_blank"
              rel="noopener noreferrer"
              className="block"
            >
              <img
                src={photo.thumbnail}
                alt="Task photo"
                className="w-24 h-24 object-cover rounded-md hover:opacity-80 transition-opacity"
              />
            </a>
            <button
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                onDeletePhoto(photo.id);
              }}
              className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}