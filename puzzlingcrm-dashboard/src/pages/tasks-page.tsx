import { useCallback, useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { getConfigOrNull } from "@/api/client"
import {
  getTasks,
  updateTaskStatus,
  deleteTask,
  quickAddTask,
  type TaskItem,
  type GetTasksResponse,
} from "@/api/tasks"
import { cn } from "@/lib/utils"
import {
  Loader2,
  LayoutGrid,
  List,
  Plus,
  FolderOpen,
  Calendar,
  User,
  GripVertical,
  Trash2,
} from "lucide-react"

export function TasksPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [view, setView] = useState<"board" | "list">("board")
  const [data, setData] = useState<GetTasksResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [projectFilter, setProjectFilter] = useState<string>("")
  const [staffFilter, setStaffFilter] = useState<string>("")
  const [priorityFilter, setPriorityFilter] = useState("")
  const [labelFilter, setLabelFilter] = useState("")
  const [addOpen, setAddOpen] = useState(false)
  const [deleteTaskId, setDeleteTaskId] = useState<number | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [addForm, setAddForm] = useState({
    title: "",
    project_id: 0,
    assigned_to: 0,
    status_slug: "",
  })
  const [draggedTask, setDraggedTask] = useState<TaskItem | null>(null)
  const [dragOverStatus, setDragOverStatus] = useState<string | null>(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getTasks({
        s: search || undefined,
        project_filter: projectFilter ? parseInt(projectFilter, 10) : undefined,
        staff_filter: staffFilter ? parseInt(staffFilter, 10) : undefined,
        priority_filter: priorityFilter || undefined,
        label_filter: labelFilter || undefined,
        per_page: 200,
      })
      if (res.success && res.data) {
        setData(res.data)
      } else {
        setError(res.message ?? "خطا در بارگذاری وظایف")
      }
    } catch {
      setError("خطا در بارگذاری وظایف")
    } finally {
      setLoading(false)
    }
  }, [search, projectFilter, staffFilter, priorityFilter, labelFilter])

  useEffect(() => {
    load()
  }, [load])

  const handleStatusDrop = useCallback(
    async (taskId: number, newStatusSlug: string) => {
      setSubmitting(true)
      setError(null)
      try {
        const res = await updateTaskStatus(taskId, newStatusSlug)
        if (res.success) load()
        else setError(res.message ?? "خطا در به‌روزرسانی")
      } catch {
        setError("خطا در به‌روزرسانی")
      } finally {
        setSubmitting(false)
        setDraggedTask(null)
        setDragOverStatus(null)
      }
    },
    [load]
  )

  const handleDelete = useCallback(
    async (id: number) => {
      setSubmitting(true)
      setError(null)
      try {
        const res = await deleteTask(id)
        if (res.success) {
          setDeleteTaskId(null)
          load()
        } else setError(res.message ?? "خطا در حذف")
      } catch {
        setError("خطا در حذف")
      } finally {
        setSubmitting(false)
      }
    },
    [load]
  )

  const handleAddSubmit = useCallback(async () => {
    if (!addForm.title.trim() || !addForm.project_id || !addForm.assigned_to || !addForm.status_slug) {
      setError("عنوان، پروژه، مسئول و وضعیت الزامی هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await quickAddTask({
        title: addForm.title.trim(),
        project_id: addForm.project_id,
        assigned_to: addForm.assigned_to,
        status_slug: addForm.status_slug,
      })
      if (res.success) {
        setAddOpen(false)
        setAddForm({ title: "", project_id: 0, assigned_to: 0, status_slug: "" })
        load()
      } else setError(res.message ?? "خطا در ایجاد وظیفه")
    } catch {
      setError("خطا در ایجاد وظیفه")
    } finally {
      setSubmitting(false)
    }
  }, [addForm, load])

  const statuses = data?.statuses ?? []
  const tasks = data?.tasks ?? []
  const stats = data?.stats ?? { total_tasks: 0, active_tasks: 0, completed_tasks: 0, total_projects: 0 }
  const projects = data?.projects ?? []
  const staff = data?.staff ?? []
  const isManager = (stats.total_projects ?? 0) > 0 || staff.length > 0

  const tasksByStatus = (statusSlug: string) =>
    tasks.filter((t) => t.status_slug === statusSlug)

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-xl font-semibold">وظایف</h2>
        <div className="flex gap-2">
          <Button
            variant={view === "board" ? "default" : "outline"}
            size="sm"
            onClick={() => setView("board")}
            className="gap-2"
          >
            <LayoutGrid className="h-4 w-4" />
            بُرد
          </Button>
          <Button
            variant={view === "list" ? "default" : "outline"}
            size="sm"
            onClick={() => setView("list")}
            className="gap-2"
          >
            <List className="h-4 w-4" />
            لیست
          </Button>
          <Button size="sm" onClick={() => setAddOpen(true)} className="gap-2">
            <Plus className="h-4 w-4" />
            وظیفه جدید
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {isManager && (
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                  <FolderOpen className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">کل پروژه‌ها</p>
                  <p className="text-xl font-semibold">{stats.total_projects}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                <List className="h-5 w-5 text-muted-foreground" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">کل وظایف</p>
                <p className="text-xl font-semibold">{stats.total_tasks}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10">
                <Loader2 className="h-5 w-5 text-amber-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">فعال</p>
                <p className="text-xl font-semibold">{stats.active_tasks}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                <List className="h-5 w-5 text-green-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">تکمیل‌شده</p>
                <p className="text-xl font-semibold">{stats.completed_tasks}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-wrap gap-3">
            <Input
              placeholder="جستجو..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="max-w-[200px]"
            />
            {projects.length > 0 && (
              <Select value={projectFilter} onValueChange={setProjectFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="پروژه" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">همه</SelectItem>
                  {projects.map((p) => (
                    <SelectItem key={p.id} value={String(p.id)}>{p.title}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
            {staff.length > 0 && (
              <Select value={staffFilter} onValueChange={setStaffFilter}>
                <SelectTrigger className="w-[160px]">
                  <SelectValue placeholder="کارمند" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">همه</SelectItem>
                  {staff.map((s) => (
                    <SelectItem key={s.id} value={String(s.id)}>{s.display_name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
            {(data?.priorities?.length ?? 0) > 0 && (
              <Select value={priorityFilter} onValueChange={setPriorityFilter}>
                <SelectTrigger className="w-[120px]">
                  <SelectValue placeholder="اولویت" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">همه</SelectItem>
                  {(data?.priorities ?? []).map((p) => (
                    <SelectItem key={p.id} value={p.slug}>{p.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
            {(data?.labels?.length ?? 0) > 0 && (
              <Select value={labelFilter} onValueChange={setLabelFilter}>
                <SelectTrigger className="w-[120px]">
                  <SelectValue placeholder="برچسب" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">همه</SelectItem>
                  {(data?.labels ?? []).map((l) => (
                    <SelectItem key={l.id} value={l.slug}>{l.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
            <Button onClick={load}>اعمال فیلتر</Button>
          </div>
        </CardContent>
      </Card>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive text-sm">
          {error}
        </div>
      )}

      {loading ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : view === "board" ? (
        <div className="flex gap-4 overflow-x-auto pb-4">
          {statuses.map((status) => (
            <div
              key={status.slug}
              className={cn(
                "min-w-[280px] rounded-lg border-2 bg-muted/30 p-3 transition-colors",
                dragOverStatus === status.slug && "border-primary bg-primary/5"
              )}
              onDragOver={(e) => {
                e.preventDefault()
                setDragOverStatus(status.slug)
              }}
              onDragLeave={() => setDragOverStatus(null)}
              onDrop={(e) => {
                e.preventDefault()
                setDragOverStatus(null)
                const task = draggedTask
                if (task && task.status_slug !== status.slug) {
                  handleStatusDrop(task.id, status.slug)
                }
              }}
            >
              <div className="mb-3 flex items-center justify-between">
                <span className="font-medium">{status.name}</span>
                <Badge variant="secondary">{tasksByStatus(status.slug).length}</Badge>
              </div>
              <div className="space-y-2">
                {tasksByStatus(status.slug).map((task) => (
                  <div
                    key={task.id}
                    draggable
                    onDragStart={() => setDraggedTask(task)}
                    onDragEnd={() => setDraggedTask(null)}
                    className="flex items-start gap-2 rounded-md border bg-background p-3 shadow-sm cursor-grab active:cursor-grabbing"
                  >
                    <GripVertical className="h-4 w-4 shrink-0 text-muted-foreground" />
                    <div className="min-w-0 flex-1">
                      <p className="font-medium text-sm">{task.title}</p>
                      {task.project_title && (
                        <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                          <FolderOpen className="h-3 w-3" />
                          {task.project_title}
                        </p>
                      )}
                      {task.due_date && (
                        <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                          <Calendar className="h-3 w-3" />
                          {task.due_date}
                        </p>
                      )}
                      {task.assigned_name && (
                        <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                          <User className="h-3 w-3" />
                          {task.assigned_name}
                        </p>
                      )}
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 shrink-0 text-destructive hover:text-destructive"
                      onClick={() => setDeleteTaskId(task.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <Card>
          <CardContent className="pt-6">
            {tasks.length === 0 ? (
              <div className="py-12 text-center text-muted-foreground">هیچ وظیفه‌ای یافت نشد.</div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>عنوان</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead>پروژه</TableHead>
                    <TableHead>مسئول</TableHead>
                    <TableHead>سررسید</TableHead>
                    <TableHead className="w-[80px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tasks.map((task) => (
                    <TableRow key={task.id}>
                      <TableCell className="font-medium">{task.title}</TableCell>
                      <TableCell><Badge variant="secondary">{task.status_name}</Badge></TableCell>
                      <TableCell>{task.project_title || "—"}</TableCell>
                      <TableCell>{task.assigned_name || "—"}</TableCell>
                      <TableCell>{task.due_date ? task.due_date.slice(0, 10) : "—"}</TableCell>
                      <TableCell>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-8 w-8 text-destructive hover:text-destructive"
                          onClick={() => setDeleteTaskId(task.id)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      )}

      {/* Add task dialog */}
      <Dialog open={addOpen} onOpenChange={setAddOpen}>
        <DialogContent className="sm:max-w-md" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>وظیفه جدید</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <label className="text-sm font-medium">عنوان *</label>
              <Input
                value={addForm.title}
                onChange={(e) => setAddForm((f) => ({ ...f, title: e.target.value }))}
                placeholder="عنوان وظیفه"
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">پروژه *</label>
              <Select
                value={addForm.project_id ? String(addForm.project_id) : ""}
                onValueChange={(v) => setAddForm((f) => ({ ...f, project_id: parseInt(v, 10) || 0 }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="انتخاب پروژه" />
                </SelectTrigger>
                <SelectContent>
                  {projects.map((p) => (
                    <SelectItem key={p.id} value={String(p.id)}>{p.title}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">مسئول *</label>
              <Select
                value={addForm.assigned_to ? String(addForm.assigned_to) : ""}
                onValueChange={(v) => setAddForm((f) => ({ ...f, assigned_to: parseInt(v, 10) || 0 }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="انتخاب مسئول" />
                </SelectTrigger>
                <SelectContent>
                  {staff.map((s) => (
                    <SelectItem key={s.id} value={String(s.id)}>{s.display_name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">وضعیت *</label>
              <Select
                value={addForm.status_slug}
                onValueChange={(v) => setAddForm((f) => ({ ...f, status_slug: v }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="انتخاب وضعیت" />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((s) => (
                    <SelectItem key={s.slug} value={s.slug}>{s.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAddOpen(false)}>انصراف</Button>
            <Button onClick={handleAddSubmit} disabled={submitting}>
              {submitting && <Loader2 className="h-4 w-4 animate-spin me-2" />}
              ایجاد
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete confirm */}
      <AlertDialog open={deleteTaskId !== null} onOpenChange={(open: boolean) => !open && setDeleteTaskId(null)}>
        <AlertDialogContent dir={isRtl ? "rtl" : "ltr"}>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف وظیفه</AlertDialogTitle>
            <AlertDialogDescription>
              آیا از حذف این وظیفه اطمینان دارید؟
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>انصراف</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={() => deleteTaskId !== null && handleDelete(deleteTaskId)}
              disabled={submitting}
            >
              {submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : "حذف"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
