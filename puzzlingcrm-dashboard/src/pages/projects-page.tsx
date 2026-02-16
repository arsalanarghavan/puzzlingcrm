import { useCallback, useEffect, useState } from "react"
import { Link, useNavigate, useSearchParams } from "react-router-dom"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { DatePicker } from "@/components/ui/date-picker"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { SELECT_ALL_VALUE } from "@/lib/constants"
import { getConfigOrNull } from "@/api/client"
import {
  getProjects,
  getProject,
  manageProject,
  deleteProject,
  type Project,
} from "@/api/projects"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Pencil, Trash2, List, FolderOpen, ArrowRight, User, Users, Calendar, MoreVertical, Eye, ChevronRight, ChevronLeft, FileText } from "lucide-react"
import type { ProjectDetail } from "@/api/projects"

function ProjectDetailView({
  projectId,
  isRtl,
  onBack,
  onEdit,
}: {
  projectId: number
  isRtl: boolean
  onBack: () => void
  onEdit: () => void
}) {
  const [detail, setDetail] = useState<ProjectDetail | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(null)
    getProject(projectId)
      .then((res) => {
        if (cancelled) return
        if (res.success && res.data?.project) {
          setDetail(res.data.project)
        } else {
          setError(res.message ?? "پروژه یافت نشد.")
        }
      })
      .catch(() => {
        if (!cancelled) setError("خطا در بارگذاری پروژه.")
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => { cancelled = true }
  }, [projectId])

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12" dir={isRtl ? "rtl" : "ltr"}>
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    )
  }
  if (error || !detail) {
    return (
      <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
        <Button variant="ghost" onClick={onBack}>← بازگشت به لیست پروژه‌ها</Button>
        <p className="text-destructive">{error ?? "پروژه یافت نشد."}</p>
      </div>
    )
  }

  const priorityLabel = { high: "زیاد", medium: "متوسط", low: "کم" }[detail.priority] ?? detail.priority
  const tasks = detail.tasks ?? []

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <Button variant="ghost" onClick={onBack} className={cn("gap-2", isRtl && "flex-row-reverse")}>
          <ArrowRight className={cn("h-4 w-4", isRtl && "rotate-180")} />
          بازگشت به لیست پروژه‌ها
        </Button>
        <Button variant="outline" size="sm" onClick={onEdit} className="gap-2">
          <Pencil className="h-4 w-4" />
          ویرایش پروژه
        </Button>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <h1 className="text-2xl font-semibold">{detail.title}</h1>
        <Badge variant="secondary">{detail.status_name ?? ""}</Badge>
        <Badge variant="outline">{priorityLabel}</Badge>
      </div>

      <Card>
        <CardContent className="pt-6">
          <h3 className="text-lg font-medium mb-4">جزئیات پروژه</h3>
          <div className="grid gap-4 sm:grid-cols-2">
            {detail.customer_name && (
              <div className="flex items-center gap-2">
                <User className="h-4 w-4 text-muted-foreground" />
                <span>مشتری:</span>
                <span className="font-medium">{detail.customer_name}</span>
              </div>
            )}
            {detail.manager_name && (
              <div className="flex items-center gap-2">
                <User className="h-4 w-4 text-muted-foreground" />
                <span>مدیر پروژه:</span>
                <span className="font-medium">{detail.manager_name}</span>
              </div>
            )}
            {detail.start_date && (
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <span>تاریخ شروع:</span>
                <span>{detail.start_date}</span>
              </div>
            )}
            {detail.end_date && (
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <span>تاریخ پایان:</span>
                <span>{detail.end_date}</span>
              </div>
            )}
            {detail.assigned_members && detail.assigned_members.length > 0 && (
              <div className="flex items-start gap-2 sm:col-span-2">
                <Users className="h-4 w-4 text-muted-foreground mt-0.5" />
                <div>
                  <span className="block mb-1">اعضای تیم:</span>
                  <div className="flex flex-wrap gap-1">
                    {detail.assigned_members.map((m) => (
                      <Badge key={m.id} variant="outline">{m.display_name}</Badge>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>
          {detail.content && (
            <div className="mt-4 pt-4 border-t">
              <p className="text-sm text-muted-foreground whitespace-pre-wrap">{detail.content}</p>
            </div>
          )}
        </CardContent>
      </Card>

      {(detail.total_tasks !== undefined && detail.total_tasks > 0) && (
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-medium">پیشرفت وظایف</h3>
              <span className="text-sm text-muted-foreground">
                {detail.completed_tasks ?? 0} از {detail.total_tasks} تکمیل شده
                {detail.completion_percentage != null && ` (${detail.completion_percentage}%)`}
              </span>
            </div>
            {detail.completion_percentage != null && (
              <div className="h-2 w-full rounded-full bg-muted overflow-hidden">
                <div
                  className="h-full bg-primary transition-all"
                  style={{ width: `${Math.min(100, detail.completion_percentage)}%` }}
                />
              </div>
            )}
            {tasks.length > 0 && (
              <ul className="mt-4 space-y-2">
                {tasks.slice(0, 10).map((t) => (
                  <li key={t.id} className="flex items-center justify-between py-2 border-b last:border-0">
                    <span>{t.title}</span>
                    <Badge variant="secondary">{t.status_name}</Badge>
                  </li>
                ))}
                {tasks.length > 10 && (
                  <li className="text-sm text-muted-foreground pt-2">
                    و {tasks.length - 10} وظیفهٔ دیگر...
                  </li>
                )}
              </ul>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}

export function ProjectsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const editId = searchParams.get("project_id") ? parseInt(searchParams.get("project_id")!, 10) : null
  const initialTab = searchParams.get("action") === "new" ? "new" : editId ? "form" : "list"

  const [tab, setTab] = useState<"list" | "new" | "form">(initialTab)
  const [projects, setProjects] = useState<Project[]>([])
  const [contracts, setContracts] = useState<{ id: number; title: string; customer_name: string }[]>([])
  const [statuses, setStatuses] = useState<{ id: number; name: string }[]>([])
  const [managers, setManagers] = useState<{ id: number; display_name: string }[]>([])
  const [totalPages, setTotalPages] = useState(1)
  const [currentPage, setCurrentPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [contractFilter, setContractFilter] = useState("")
  const [statusFilter, setStatusFilter] = useState("")
  const [submitting, setSubmitting] = useState(false)
  const [wizardStep, setWizardStep] = useState(1)
  const [form, setForm] = useState({
    project_title: "",
    contract_id: 0,
    project_manager: 0,
    project_status: 0,
    project_content: "",
    start_date: "",
    end_date: "",
    priority: "medium",
  })

  const loadList = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getProjects({
        s: search || undefined,
        contract_id: contractFilter ? parseInt(contractFilter, 10) : undefined,
        status_filter: statusFilter ? parseInt(statusFilter, 10) : undefined,
        paged: currentPage,
      })
      if (res.success && res.data) {
        setProjects(res.data.projects ?? [])
        setContracts(res.data.contracts ?? [])
        setStatuses(res.data.statuses ?? [])
        setManagers(res.data.managers ?? [])
        setTotalPages(res.data.total_pages ?? 1)
      } else {
        setError(res.message ?? "خطا در بارگذاری پروژه‌ها")
      }
    } catch {
      setError("خطا در بارگذاری پروژه‌ها")
    } finally {
      setLoading(false)
    }
  }, [search, contractFilter, statusFilter, currentPage])

  const loadProject = useCallback(async (id: number) => {
    setLoading(true)
    setError(null)
    try {
      const res = await getProject(id)
      if (res.success && res.data) {
        const p = res.data.project
        setManagers(res.data.managers ?? [])
        setContracts(res.data.contracts ?? [])
        setStatuses(res.data.statuses ?? [])
        if (p) {
          setForm({
            project_title: p.title ?? "",
            contract_id: p.contract_id ?? 0,
            project_manager: p.project_manager ?? 0,
            project_status: p.project_status ?? 0,
            project_content: p.content ?? "",
            start_date: p.start_date ? p.start_date.slice(0, 10) : "",
            end_date: p.end_date ? p.end_date.slice(0, 10) : "",
            priority: p.priority ?? "medium",
          })
        } else {
          setError(res.message ?? "خطا در بارگذاری پروژه")
        }
      } else {
        setError(res.message ?? "خطا در بارگذاری پروژه")
      }
    } catch {
      setError("خطا در بارگذاری پروژه")
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (tab === "list") loadList()
  }, [tab, loadList])

  useEffect(() => {
    if (editId && tab === "form") loadProject(editId)
  }, [tab, editId])

  useEffect(() => {
    if (tab === "new") {
      getProjects({}).then((res) => {
        if (res.success && res.data) {
          setContracts(res.data.contracts ?? [])
          setStatuses(res.data.statuses ?? [])
          setManagers(res.data.managers ?? [])
        }
      })
    }
  }, [tab])

  const openNew = () => {
    navigate("/projects?action=new")
    setTab("new")
    setWizardStep(1)
    setForm({
      project_title: "",
      contract_id: 0,
      project_manager: 0,
      project_status: 0,
      project_content: "",
      start_date: "",
      end_date: "",
      priority: "medium",
    })
  }

  const openEdit = (p: Project) => {
    navigate(`/projects?action=edit&project_id=${p.id}`)
    setTab("form")
    loadProject(p.id)
  }

  const backToList = () => {
    navigate("/projects")
    setTab("list")
    loadList()
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.project_title.trim() || !form.contract_id || !form.project_status) {
      setError("نام پروژه، قرارداد و وضعیت الزامی هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await manageProject({
        project_id: editId ?? undefined,
        project_title: form.project_title.trim(),
        contract_id: form.contract_id,
        project_content: form.project_content || undefined,
        project_status: form.project_status,
        project_manager: form.project_manager || undefined,
        project_start_date: form.start_date || undefined,
        project_end_date: form.end_date || undefined,
        project_priority: form.priority,
      })
      if (res.success) {
        backToList()
        if ((res.data as { reload?: boolean })?.reload) window.location.reload()
      } else {
        setError(res.message ?? "خطا در ذخیره")
      }
    } catch {
      setError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }

  const handleDelete = async (p: Project & { delete_nonce?: string }) => {
    const nonce = p.delete_nonce
    if (!nonce || !confirm("آیا از حذف این پروژه اطمینان دارید؟")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await deleteProject(p.id, nonce)
      if (res.success) loadList()
      else setError(res.message ?? "خطا در حذف")
    } catch {
      setError("خطا در حذف")
    } finally {
      setSubmitting(false)
    }
  }

  const showForm = tab === "new" || tab === "form"
  const viewId = searchParams.get("action") === "view" ? (searchParams.get("project_id") ? parseInt(searchParams.get("project_id")!, 10) : null) : null

  if (viewId) {
    return (
      <ProjectDetailView
        projectId={viewId}
        isRtl={isRtl}
        onBack={() => { navigate("/projects"); setTab("list") }}
        onEdit={() => { navigate(`/projects?action=edit&project_id=${viewId}`); setTab("form"); loadProject(viewId) }}
      />
    )
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">پروژه‌ها</h1>
        <div className="flex gap-2">
          <Button variant={tab === "list" ? "default" : "outline"} onClick={() => { setTab("list"); navigate("/projects") }}>
            <List className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            لیست
          </Button>
          <Button variant={showForm ? "default" : "outline"} onClick={openNew}>
            <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            ایجاد پروژه
          </Button>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive">
          {error}
        </div>
      )}

      {showForm ? (
        <Card>
          <CardContent className="pt-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold">{editId ? "ویرایش پروژه" : "ایجاد پروژه جدید"}</h3>
              <Button variant="outline" size="sm" onClick={backToList}>بازگشت</Button>
            </div>
            {!editId && (
              <div className="flex items-center gap-2 mb-6 pb-4 border-b">
                <div className={cn("w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium", wizardStep === 1 ? "bg-primary text-primary-foreground" : "bg-primary/20 text-primary")}>1</div>
                <ChevronRight className="h-4 w-4 text-muted-foreground" />
                <div className={cn("w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium", wizardStep === 2 ? "bg-primary text-primary-foreground" : wizardStep > 1 ? "bg-primary/20 text-primary" : "bg-muted text-muted-foreground")}>2</div>
                <span className="text-sm text-muted-foreground mr-2">
                  {wizardStep === 1 ? "انتخاب قرارداد" : "جزئیات پروژه"}
                </span>
              </div>
            )}
            <form onSubmit={handleSubmit} className="space-y-4">
              {editId ? (
                <>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label>نام پروژه *</Label>
                    <Input value={form.project_title} onChange={(e) => setForm((f) => ({ ...f, project_title: e.target.value }))} required />
                  </div>
                  <div className="space-y-2">
                    <Label>قرارداد / مشتری *</Label>
                    <Select value={form.contract_id ? String(form.contract_id) : ""} onValueChange={(v) => setForm((f) => ({ ...f, contract_id: parseInt(v, 10) || 0 }))} disabled>
                      <SelectTrigger><SelectValue placeholder="انتخاب قرارداد" /></SelectTrigger>
                      <SelectContent>
                        {contracts.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.title} ({c.customer_name})</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                <div className="space-y-2">
                  <Label>مدیر پروژه</Label>
                  <Select
                    value={form.project_manager ? String(form.project_manager) : ""}
                    onValueChange={(v) => setForm((f) => ({ ...f, project_manager: parseInt(v, 10) || 0 }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="انتخاب مدیر" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">—</SelectItem>
                      {managers.map((m) => (
                        <SelectItem key={m.id} value={String(m.id)}>{m.display_name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>وضعیت *</Label>
                  <Select
                    value={form.project_status ? String(form.project_status) : ""}
                    onValueChange={(v) => setForm((f) => ({ ...f, project_status: parseInt(v, 10) || 0 }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="انتخاب وضعیت" />
                    </SelectTrigger>
                    <SelectContent>
                      {statuses.map((s) => (
                        <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>تاریخ شروع</Label>
                  <DatePicker value={form.start_date} onChange={(v) => setForm((f) => ({ ...f, start_date: v }))} />
                </div>
                <div className="space-y-2">
                  <Label>تاریخ پایان</Label>
                  <DatePicker value={form.end_date} onChange={(v) => setForm((f) => ({ ...f, end_date: v }))} />
                </div>
              </div>
              <div className="space-y-2">
                <Label>توضیحات</Label>
                <textarea
                  className="flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                  value={form.project_content}
                  onChange={(e) => setForm((f) => ({ ...f, project_content: e.target.value }))}
                  rows={6}
                />
              </div>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                ذخیره
              </Button>
                </>
              ) : (
                <>
              {wizardStep === 1 && (
                <div className="space-y-4">
                  <div className="flex items-center gap-2 text-muted-foreground mb-4">
                    <FileText className="h-5 w-5" />
                    <span>انتخاب قرارداد برای متصل کردن پروژه</span>
                  </div>
                  <div className="space-y-2">
                    <Label>قرارداد / مشتری *</Label>
                    <Select value={form.contract_id ? String(form.contract_id) : ""} onValueChange={(v) => setForm((f) => ({ ...f, contract_id: parseInt(v, 10) || 0 }))}>
                      <SelectTrigger>
                        <SelectValue placeholder="انتخاب قرارداد" />
                      </SelectTrigger>
                      <SelectContent>
                        {contracts.map((c) => (
                          <SelectItem key={c.id} value={String(c.id)}>
                            {c.title} ({c.customer_name})
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <Button type="button" onClick={() => setWizardStep(2)} disabled={!form.contract_id}>
                    بعدی
                    <ChevronLeft className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                  </Button>
                </div>
              )}
              {wizardStep === 2 && (
                <>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label>نام پروژه *</Label>
                    <Input value={form.project_title} onChange={(e) => setForm((f) => ({ ...f, project_title: e.target.value }))} required />
                  </div>
                  <div className="space-y-2">
                    <Label>قرارداد (انتخاب شده)</Label>
                    <Select value={String(form.contract_id)} onValueChange={() => {}} disabled>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {contracts.filter((c) => c.id === form.contract_id).map((c) => (
                          <SelectItem key={c.id} value={String(c.id)}>{c.title} ({c.customer_name})</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>مدیر پروژه</Label>
                    <Select value={form.project_manager ? String(form.project_manager) : ""} onValueChange={(v) => setForm((f) => ({ ...f, project_manager: parseInt(v, 10) || 0 }))}>
                      <SelectTrigger><SelectValue placeholder="انتخاب مدیر" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="0">—</SelectItem>
                        {managers.map((m) => <SelectItem key={m.id} value={String(m.id)}>{m.display_name}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>وضعیت *</Label>
                    <Select value={form.project_status ? String(form.project_status) : ""} onValueChange={(v) => setForm((f) => ({ ...f, project_status: parseInt(v, 10) || 0 }))}>
                      <SelectTrigger><SelectValue placeholder="انتخاب وضعیت" /></SelectTrigger>
                      <SelectContent>
                        {statuses.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>تاریخ شروع</Label>
                    <DatePicker value={form.start_date} onChange={(v) => setForm((f) => ({ ...f, start_date: v }))} />
                  </div>
                  <div className="space-y-2">
                    <Label>تاریخ پایان</Label>
                    <DatePicker value={form.end_date} onChange={(v) => setForm((f) => ({ ...f, end_date: v }))} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>توضیحات</Label>
                  <textarea className="flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" value={form.project_content} onChange={(e) => setForm((f) => ({ ...f, project_content: e.target.value }))} rows={6} />
                </div>
                <div className="flex gap-4">
                  <Button type="button" variant="outline" onClick={() => setWizardStep(1)}>
                    <ChevronRight className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                    قبلی
                  </Button>
                  <Button type="submit" disabled={submitting}>
                    {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                    ایجاد پروژه
                  </Button>
                </div>
                </>
              )}
                </>
              )}
            </form>
          </CardContent>
        </Card>
      ) : (
        <>
          <Card>
            <CardContent className="pt-6">
              <div className="flex flex-wrap gap-4 mb-4">
                <Input placeholder="جستجو..." value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-[200px]" />
                <Select
                  value={contractFilter === "" ? SELECT_ALL_VALUE : contractFilter}
                  onValueChange={(v) => setContractFilter(v === SELECT_ALL_VALUE ? "" : v)}
                >
                  <SelectTrigger className="w-[180px]">
                    <SelectValue placeholder="قرارداد" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_ALL_VALUE}>همه</SelectItem>
                    {contracts.map((c) => (
                      <SelectItem key={c.id} value={String(c.id)}>{c.title}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Select
                  value={statusFilter === "" ? SELECT_ALL_VALUE : statusFilter}
                  onValueChange={(v) => setStatusFilter(v === SELECT_ALL_VALUE ? "" : v)}
                >
                  <SelectTrigger className="w-[120px]">
                    <SelectValue placeholder="وضعیت" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_ALL_VALUE}>همه</SelectItem>
                    {statuses.map((s) => (
                      <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Button onClick={loadList}>فیلتر</Button>
              </div>
            </CardContent>
          </Card>
          {loading ? (
            <Card>
              <CardContent className="flex items-center justify-center py-12">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
              </CardContent>
            </Card>
          ) : projects.length === 0 ? (
            <Card>
              <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                <FolderOpen className="h-16 w-16 text-muted-foreground mb-4" />
                <p className="text-muted-foreground">هیچ پروژه‌ای یافت نشد.</p>
              </CardContent>
            </Card>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {projects.map((p) => (
                <Card key={p.id} className="overflow-hidden">
                  <CardContent className="pt-6">
                    <div className="flex flex-col items-center text-center mb-4">
                      <div className="h-16 w-16 rounded-lg bg-muted flex items-center justify-center mb-2">
                        <FolderOpen className="h-8 w-8 text-muted-foreground" />
                      </div>
                      <Link
                        to={`/projects?action=view&project_id=${p.id}`}
                        className="font-medium text-primary hover:underline line-clamp-2"
                      >
                        {p.title}
                      </Link>
                      <p className="text-sm text-muted-foreground mt-1">{p.customer_name}</p>
                    </div>
                    <div className="flex items-center justify-between">
                      <Badge variant="secondary">{p.status_name}</Badge>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem asChild>
                            <Link to={`/projects?action=view&project_id=${p.id}`}>
                              <Eye className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                              مشاهده
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => openEdit(p)}>
                            <Pencil className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                            ویرایش
                          </DropdownMenuItem>
                          {(p as Project & { delete_nonce?: string }).delete_nonce && (
                            <DropdownMenuItem
                              className="text-destructive"
                              onClick={() => handleDelete(p as Project & { delete_nonce?: string })}
                              disabled={submitting}
                            >
                              <Trash2 className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                              حذف
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
          {totalPages > 1 && (
            <div className="flex justify-center gap-2">
              <Button variant="outline" size="sm" disabled={currentPage <= 1} onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}>قبلی</Button>
              <span className="flex items-center px-2 text-sm">صفحه {currentPage} از {totalPages}</span>
              <Button variant="outline" size="sm" disabled={currentPage >= totalPages} onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}>بعدی</Button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
