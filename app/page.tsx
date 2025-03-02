"use client";

import { useState, useEffect } from "react";
import { Task, Priority } from "@/types/task";
import TaskList from "@/components/TaskList";
import NewTaskDialog from "@/components/NewTaskDialog";
import { PlusCircle, RefreshCcw, Clock, AlertTriangle, CheckCircle } from "lucide-react";
import { DragDropContext, DropResult } from 'react-beautiful-dnd';

// Use relative path for production, absolute for development
const API_BASE = typeof window !== 'undefined' && window.location.hostname === 'localhost' 
  ? "/api" // For local development
  : "/tasks/api"; // For production with subdirectory

// Log the API base for debugging
console.log('API_BASE:', API_BASE);

export default function Home() {
  const [tasks, setTasks] = useState<Task[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isNewTaskDialogOpen, setIsNewTaskDialogOpen] = useState(false);
  const [activeNoteTask, setActiveNoteTask] = useState<string | null>(null);

  // Stats
  const totalTasks = tasks.length;
  const inProgressCount = tasks.filter(task => task.inProgress).length;
  const issuesCount = tasks.filter(task => task.hasIssue).length;
  const completedCount = tasks.filter(task => task.completed).length;

  useEffect(() => {
    fetchTasks();
  }, []);

  const fetchTasks = async () => {
    try {
      setIsLoading(true);
      
      const response = await fetch(`${API_BASE}/tasks.php`);
      if (!response.ok) {
        throw new Error('Failed to fetch tasks');
      }
      const data = await response.json();
      setTasks(data || []);
      
    } catch (error) {
      console.error('Error fetching tasks:', error);
      alert('Failed to fetch tasks from database: ' + error);
    } finally {
      setIsLoading(false);
    }
  };

  const onDragEnd = async (result: DropResult) => {
    if (!result.destination) return;

    const items = Array.from(tasks);
    const [reorderedItem] = items.splice(result.source.index, 1);
    items.splice(result.destination.index, 0, reorderedItem);

    // Update positions
    const updatedItems = items.map((item, index) => ({
      ...item,
      position: index + 1
    }));

    setTasks(updatedItems);

    // Update in database
    try {
      for (const task of updatedItems) {
        await fetch(`${API_BASE}/tasks.php`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(task)
        });
      }
    } catch (error) {
      console.error('Failed to update task positions:', error);
    }
  };

  const toggleExpand = (id: string) => {
    setTasks(tasks.map(task => {
      if (task.id === id) {
        const updated = { ...task, expanded: !task.expanded };
        updateTaskInDatabase(updated);
        return updated;
      }
      return task;
    }));
  };

  const toggleIssue = (id: string) => {
    setTasks(tasks.map(task => {
      if (task.id === id) {
        const updated = { ...task, hasIssue: !task.hasIssue };
        updateTaskInDatabase(updated);
        return updated;
      }
      return task;
    }));
  };

  const toggleInProgress = (id: string) => {
    setTasks(tasks.map(task => {
      if (task.id === id) {
        const updated = { ...task, inProgress: !task.inProgress };
        updateTaskInDatabase(updated);
        return updated;
      }
      return task;
    }));
  };

  const toggleComplete = (id: string) => {
    setTasks(tasks.map(task => {
      if (task.id === id) {
        const updated = { ...task, completed: !task.completed, inProgress: false };
        updateTaskInDatabase(updated);
        return updated;
      }
      return task;
    }));
  };

  const updateTaskInDatabase = async (task: Task) => {
    try {
      await fetch(`${API_BASE}/tasks.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(task)
      });
    } catch (error) {
      console.error('Failed to update task:', error);
    }
  };

  const deleteTask = async (id: string) => {
    try {
      await fetch(`${API_BASE}/tasks.php?id=${id}`, {
        method: 'DELETE'
      });
      setTasks(tasks.filter(task => task.id !== id));
    } catch (error) {
      console.error('Failed to delete task:', error);
    }
  };

  const deletePhoto = async (taskId: string, photoId: string) => {
    try {
      await fetch(`${API_BASE}/photos.php?id=${photoId}`, {
        method: 'DELETE'
      });
      
      setTasks(tasks.map(task => {
        if (task.id === taskId) {
          return {
            ...task,
            photos: task.photos.filter(photo => photo.id !== photoId)
          };
        }
        return task;
      }));
    } catch (error) {
      console.error('Failed to delete photo:', error);
    }
  };

  const addNote = (taskId: string) => {
    setActiveNoteTask(taskId === activeNoteTask ? null : taskId);
  };

  const submitNote = async (taskId: string, content: string) => {
    try {
      const response = await fetch(`${API_BASE}/notes.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ taskId, content })
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(`Failed to add note: ${errorData.error || 'Unknown error'}`);
      }
      
      const data = await response.json();
      
      setTasks(tasks.map(task => {
        if (task.id === taskId) {
          return {
            ...task,
            notes: [
              ...task.notes,
              {
                id: data.note.id,
                content,
                timestamp: data.note.timestamp,
              },
            ],
          };
        }
        return task;
      }));
      
      setActiveNoteTask(null);
    } catch (error) {
      console.error('Failed to add note:', error);
      alert('Failed to save note to database: ' + error);
    }
  };

  const addPhoto = async (taskId: string, event: React.ChangeEvent<HTMLInputElement>) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    try {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        const formData = new FormData();
        formData.append('photo', file);
        formData.append('taskId', taskId);

        const response = await fetch(`${API_BASE}/photos.php`, {
          method: 'POST',
          body: formData
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(`Failed to upload photo: ${errorData.error || 'Unknown error'}`);
        }

        const data = await response.json();
        
        setTasks(tasks.map(task => {
          if (task.id === taskId) {
            return {
              ...task,
              photos: [
                ...task.photos,
                {
                  id: data.photo.id,
                  url: data.photo.url,
                  thumbnail: data.photo.thumbnail,
                },
              ],
            };
          }
          return task;
        }));
      }
    } catch (error) {
      console.error('Failed to upload photo:', error);
      alert('Failed to upload photo to database: ' + error);
    }
  };

  const createNewTask = async (roNumber: string, title: string, description: string, priority: Priority) => {
    try {
      const newTaskData = {
        roNumber,
        title,
        description,
        priority,
        expanded: true,
        hasIssue: false,
        inProgress: false,
        completed: false,
        position: 1 // Set new task to position 1
      };

      const response = await fetch(`${API_BASE}/tasks.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newTaskData)
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(`Failed to create task: ${errorData.error || 'Unknown error'}`);
      }

      const newTask = await response.json();
      setTasks([newTask, ...tasks.map(task => ({ ...task, position: task.position + 1 }))]);
      
      setIsNewTaskDialogOpen(false);
    } catch (error) {
      console.error('Failed to create task:', error);
      alert('Failed to save task to database: ' + error);
    }
  };

  const updateTaskPriority = (taskId: string, priority: Priority) => {
    setTasks(tasks.map(task => {
      if (task.id === taskId) {
        const updated = { ...task, priority };
        updateTaskInDatabase(updated);
        return updated;
      }
      return task;
    }));
  };

  return (
    <div className="min-h-screen bg-background p-4 md:p-6">
      <div className="max-w-[1200px] mx-auto">
        <header className="mb-8">
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center gap-3">
              <div className="bg-yellow-400 text-yellow-950 p-3 rounded-lg">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                  <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z" stroke="currentColor" strokeWidth="2"/>
                  <path d="M9 12L11 14L15 10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div>
                <h1 className="text-3xl font-bold text-foreground">Repair-Trac</h1>
                <p className="text-muted-foreground mt-1">Track, Sync, and Complete Repairs Together</p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div className="bg-card rounded-lg p-4 shadow-md">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold">{totalTasks}</h2>
              </div>
              <p className="text-muted-foreground">Total Tasks</p>
            </div>
            
            <div className="bg-blue-900/20 rounded-lg p-4 shadow-md">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold flex items-center gap-2">
                  <Clock className="w-5 h-5 text-blue-400" />
                  {inProgressCount}
                </h2>
              </div>
              <p className="text-muted-foreground">In Progress</p>
            </div>
            
            <div className="bg-red-900/20 rounded-lg p-4 shadow-md">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold flex items-center gap-2">
                  <AlertTriangle className="w-5 h-5 text-red-400" />
                  {issuesCount}
                </h2>
              </div>
              <p className="text-muted-foreground">Issues</p>
            </div>
            
            <div className="bg-green-900/20 rounded-lg p-4 shadow-md">
              <div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-green-400" />
                  {completedCount}
                </h2>
              </div>
              <p className="text-muted-foreground">Completed</p>
            </div>
          </div>

          <div className="flex justify-center gap-4 mb-4">
            <button
              onClick={() => setIsNewTaskDialogOpen(true)}
              className="bg-yellow-400 text-yellow-950 rounded-lg px-4 py-2 flex items-center gap-2 hover:bg-yellow-300 transition-all shadow-[0_0_15px_rgba(250,204,21,0.5)] hover:shadow-[0_0_25px_rgba(250,204,21,0.7)] font-medium"
            >
              <PlusCircle className="w-5 h-5" />
              Add New Task
            </button>
            
            <button
              onClick={fetchTasks}
              className="bg-purple-500 text-white rounded-lg px-4 py-2 flex items-center gap-2 hover:bg-purple-400 transition-all shadow-md"
            >
              <RefreshCcw className="w-5 h-5" />
              Refresh
            </button>
          </div>
        </header>

        {isLoading ? (
          <div className="flex justify-center items-center h-40">
            <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-yellow-400"></div>
          </div>
        ) : (
          <DragDropContext onDragEnd={onDragEnd}>
            <TaskList
              tasks={tasks}
              onToggleExpand={toggleExpand}
              onToggleIssue={toggleIssue}
              onToggleInProgress={toggleInProgress}
              onToggleComplete={toggleComplete}
              onDeleteTask={deleteTask}
              onDeletePhoto={deletePhoto}
              onAddNote={addNote}
              onSubmitNote={submitNote}
              onAddPhoto={addPhoto}
              onUpdatePriority={updateTaskPriority}
              activeNoteTask={activeNoteTask}
            />
          </DragDropContext>
        )}

        <NewTaskDialog
          isOpen={isNewTaskDialogOpen}
          onClose={() => setIsNewTaskDialogOpen(false)}
          onSubmit={createNewTask}
        />
      </div>
    </div>
  );
}