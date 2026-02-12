import { useCallback, useEffect, useState } from "react"
import { useNavigate, useSearchParams } from "react-router-dom"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
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
import { SELECT_ALL_VALUE } from "@/lib/constants"
import { getConfigOrNull } from "@/api/client"
import {
  getTickets,
  getTicket,
  newTicket,
  ticketReply,
  convertTicketToTask,
  type Ticket,
  type TicketDetail,
} from "@/api/tickets"
import { cn } from "@/lib/utils"
import { Plus, Loader2, List, ArrowRight, CheckSquare } from "lucide-react"

export function TicketsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const viewId = searchParams.get("ticket_id") ? parseInt(searchParams.get("ticket_id")!, 10) : null
  const initialTab = searchParams.get("action") === "new" ? "new" : viewId ? "single" : "list"

  const [tab, setTab] = useState<"list" | "new" | "single">(initialTab)
  const [tickets, setTickets] = useState<Ticket[]>([])
  const [statuses, setStatuses] = useState<{ slug: string; name: string }[]>([])
  const [priorities, setPriorities] = useState<{ slug: string; name: string; term_id: number }[]>([])
  const [departments, setDepartments] = useState<{ id: number; name: string }[]>([])
  const [totalPages, setTotalPages] = useState(1)
  const [currentPage, setCurrentPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [statusFilter, setStatusFilter] = useState("")
  const [priorityFilter, setPriorityFilter] = useState("")
  const [departmentFilter, setDepartmentFilter] = useState("")
  const [singleTicket, setSingleTicket] = useState<TicketDetail | null>(null)
  const [singleStatuses, setSingleStatuses] = useState<{ slug: string; name: string }[]>([])
  const [singleDepartments, setSingleDepartments] = useState<{ id: number; name: string }[]>([])
  const [canManage, setCanManage] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [newForm, setNewForm] = useState({
    ticket_title: "",
    ticket_content: "",
    department: 0,
    ticket_priority: 0,
  })
  const [replyContent, setReplyContent] = useState("")
  const [replyStatus, setReplyStatus] = useState("")
  const [replyDepartment, setReplyDepartment] = useState(0)

  const loadList = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getTickets({
        s: search || undefined,
        status_filter: statusFilter || undefined,
        priority_filter: priorityFilter || undefined,
        department_filter: departmentFilter ? parseInt(departmentFilter, 10) : undefined,
        paged: currentPage,
      })
      if (res.success && res.data) {
        setTickets(res.data.tickets ?? [])
        setStatuses(res.data.statuses ?? [])
        setPriorities(res.data.priorities ?? [])
        setDepartments(res.data.departments ?? [])
        setTotalPages(res.data.total_pages ?? 1)
      } else {
        setError(res.message ?? "خطا در بارگذاری تیکت‌ها")
      }
    } catch {
      setError("خطا در بارگذاری تیکت‌ها")
    } finally {
      setLoading(false)
    }
  }, [search, statusFilter, priorityFilter, departmentFilter, currentPage])

  const loadTicket = useCallback(async (id: number) => {
    setLoading(true)
    setError(null)
    try {
      const res = await getTicket(id)
      if (res.success && res.data) {
        const ticket = res.data.ticket
        setSingleTicket(ticket)
        setSingleStatuses(res.data.statuses ?? [])
        setSingleDepartments(res.data.departments ?? [])
        setCanManage(res.data.can_manage ?? false)
        if (ticket) {
          setReplyStatus(ticket.status_slug ?? "")
          setReplyDepartment(ticket.department_id ?? 0)
        } else {
          setReplyStatus("")
          setReplyDepartment(0)
        }
      } else {
        setError(res.message ?? "خطا در بارگذاری تیکت")
      }
    } catch {
      setError("خطا در بارگذاری تیکت")
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (tab === "list") loadList()
  }, [tab, loadList])

  useEffect(() => {
    if (viewId && tab === "single") loadTicket(viewId)
  }, [tab, viewId])

  useEffect(() => {
    if (searchParams.get("action") === "new") {
      setTab("new")
      getTickets({}).then((res) => {
        if (res.success && res.data) {
          setStatuses(res.data.statuses ?? [])
          setPriorities(res.data.priorities ?? [])
          setDepartments(res.data.departments ?? [])
        }
      })
    }
  }, [])

  const openNew = () => {
    navigate("/tickets?action=new")
    setTab("new")
    setNewForm({ ticket_title: "", ticket_content: "", department: 0, ticket_priority: 0 })
  }

  const openTicket = (t: Ticket) => {
    navigate(`/tickets?ticket_id=${t.id}`)
    setTab("single")
    loadTicket(t.id)
  }

  const backToList = () => {
    navigate("/tickets")
    setTab("list")
    setSingleTicket(null)
    loadList()
  }

  const handleNewTicket = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!newForm.ticket_title.trim() || !newForm.ticket_content.trim() || !newForm.department || !newForm.ticket_priority) {
      setError("عنوان، دپارتمان، اولویت و متن تیکت الزامی هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await newTicket({
        ticket_title: newForm.ticket_title.trim(),
        ticket_content: newForm.ticket_content.trim(),
        department: newForm.department,
        ticket_priority: newForm.ticket_priority,
      })
      if (res.success) {
        backToList()
        if ((res.data as { reload?: boolean })?.reload) window.location.reload()
      } else {
        setError(res.message ?? "خطا در ثبت تیکت")
      }
    } catch {
      setError("خطا در ثبت تیکت")
    } finally {
      setSubmitting(false)
    }
  }

  const handleReply = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!singleTicket || !replyContent.trim()) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await ticketReply(
        singleTicket.id,
        replyContent.trim(),
        canManage ? { ticket_status: replyStatus || undefined, department: replyDepartment || undefined } : undefined
      )
      if (res.success) {
        setReplyContent("")
        loadTicket(singleTicket.id)
        if ((res.data as { reload?: boolean })?.reload) window.location.reload()
      } else {
        setError(res.message ?? "خطا در ثبت پاسخ")
      }
    } catch {
      setError("خطا در ثبت پاسخ")
    } finally {
      setSubmitting(false)
    }
  }

  const handleConvertToTask = async () => {
    if (!singleTicket || !canManage || !confirm("تبدیل این تیکت به وظیفه؟")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await convertTicketToTask(singleTicket.id)
      if (res.success) {
        backToList()
        if ((res.data as { reload?: boolean })?.reload) window.location.reload()
      } else {
        setError(res.message ?? "خطا در تبدیل")
      }
    } catch {
      setError("خطا در تبدیل")
    } finally {
      setSubmitting(false)
    }
  }

  if (tab === "single" && singleTicket) {
    return (
      <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
        <Button variant="ghost" onClick={backToList} className="mb-4">
          <ArrowRight className={cn("h-4 w-4", isRtl ? "me-2" : "ms-2 rotate-180")} />
          بازگشت به لیست تیکت‌ها
        </Button>
        {error && (
          <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive">
            {error}
          </div>
        )}
        <Card>
          <CardContent className="pt-6">
            <h2 className="text-xl font-semibold mb-4">{singleTicket.title}</h2>
            <div className="flex flex-wrap gap-2 mb-4 text-sm text-muted-foreground">
              <span>ارسال شده توسط: {singleTicket.author_name}</span>
              <span>در تاریخ: {singleTicket.date}</span>
              <Badge variant="secondary">{singleTicket.status_name}</Badge>
              <Badge variant="outline">{singleTicket.priority_name}</Badge>
              <span>دپارتمان: {singleTicket.department_name}</span>
              <span>مسئول: {singleTicket.assigned_to_name}</span>
            </div>
            <div className="prose prose-sm max-w-none mb-6" dangerouslySetInnerHTML={{ __html: singleTicket.content }} />
            {canManage && (
              <Button variant="outline" size="sm" onClick={handleConvertToTask} disabled={submitting} className="mb-6">
                <CheckSquare className="h-4 w-4 me-2" />
                تبدیل به وظیفه
              </Button>
            )}
            <div className="space-y-4">
              <h3 className="font-medium">پاسخ‌ها</h3>
              {singleTicket.replies.length === 0 ? (
                <p className="text-muted-foreground">هنوز پاسخی ثبت نشده است.</p>
              ) : (
                <div className="space-y-4">
                  {singleTicket.replies.map((r) => (
                    <div key={r.id} className="border rounded-lg p-4">
                      <div className="text-sm text-muted-foreground mb-2">
                        {r.author} — {r.date}
                      </div>
                      <div className="prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: r.content }} />
                    </div>
                  ))}
                </div>
              )}
              {!singleTicket.is_closed && (
                <form onSubmit={handleReply} className="space-y-4 pt-4 border-t">
                  <h4 className="font-medium">ارسال پاسخ جدید</h4>
                  {canManage && (
                    <div className="flex gap-4 flex-wrap">
                      <div className="space-y-2">
                        <Label>وضعیت</Label>
                        <Select value={replyStatus} onValueChange={setReplyStatus}>
                          <SelectTrigger className="w-[140px]">
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {(singleStatuses.length ? singleStatuses : [
                              { slug: "open", name: "باز" },
                              { slug: "in-progress", name: "در حال انجام" },
                              { slug: "closed", name: "بسته شده" },
                            ]).map((s) => (
                              <SelectItem key={s.slug} value={s.slug}>{s.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <Label>دپارتمان</Label>
                        <Select value={replyDepartment ? String(replyDepartment) : ""} onValueChange={(v) => setReplyDepartment(parseInt(v, 10) || 0)}>
                          <SelectTrigger className="w-[140px]">
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {(singleDepartments.length ? singleDepartments : departments).map((d) => (
                              <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                  )}
                  <div className="space-y-2">
                    <Label>متن پاسخ *</Label>
                    <textarea
                      className="flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                      value={replyContent}
                      onChange={(e) => setReplyContent(e.target.value)}
                      required
                      rows={6}
                    />
                  </div>
                  <Button type="submit" disabled={submitting || !replyContent.trim()}>
                    {submitting && <Loader2 className="h-4 w-4 animate-spin me-2" />}
                    ارسال پاسخ
                  </Button>
                </form>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">تیکت‌ها</h1>
        <div className="flex gap-2">
          <Button variant={tab === "list" ? "default" : "outline"} onClick={() => { setTab("list"); navigate("/tickets") }}>
            <List className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            لیست تیکت‌ها
          </Button>
          <Button variant={tab === "new" ? "default" : "outline"} onClick={openNew}>
            <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            ارسال تیکت جدید
          </Button>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive">
          {error}
        </div>
      )}

      {tab === "new" ? (
        <Card>
          <CardContent className="pt-6">
            <h3 className="text-lg font-semibold mb-4">ارسال تیکت جدید</h3>
            <form onSubmit={handleNewTicket} className="space-y-4">
              <div className="space-y-2">
                <Label>موضوع *</Label>
                <Input
                  value={newForm.ticket_title}
                  onChange={(e) => setNewForm((f) => ({ ...f, ticket_title: e.target.value }))}
                  placeholder="موضوع تیکت خود را وارد کنید..."
                  required
                />
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>دپارتمان *</Label>
                  <Select
                    value={newForm.department ? String(newForm.department) : ""}
                    onValueChange={(v) => setNewForm((f) => ({ ...f, department: parseInt(v, 10) || 0 }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="انتخاب دپارتمان" />
                    </SelectTrigger>
                    <SelectContent>
                      {departments.map((d) => (
                        <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>اولویت *</Label>
                  <Select
                    value={newForm.ticket_priority ? String(newForm.ticket_priority) : ""}
                    onValueChange={(v) => setNewForm((f) => ({ ...f, ticket_priority: parseInt(v, 10) || 0 }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="انتخاب اولویت" />
                    </SelectTrigger>
                    <SelectContent>
                      {priorities.map((p) => (
                        <SelectItem key={p.term_id} value={String(p.term_id)}>{p.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="space-y-2">
                <Label>پیام شما *</Label>
                <textarea
                  className="flex min-h-[200px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                  value={newForm.ticket_content}
                  onChange={(e) => setNewForm((f) => ({ ...f, ticket_content: e.target.value }))}
                  required
                  rows={10}
                />
              </div>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className="h-4 w-4 animate-spin me-2" />}
                ارسال تیکت
              </Button>
            </form>
          </CardContent>
        </Card>
      ) : (
        <>
          <Card>
            <CardContent className="pt-6">
              <div className="flex flex-wrap gap-4 mb-4">
                <Input
                  placeholder="جستجو..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="max-w-[200px]"
                />
                <Select
                  value={statusFilter === "" ? SELECT_ALL_VALUE : statusFilter}
                  onValueChange={(v) => setStatusFilter(v === SELECT_ALL_VALUE ? "" : v)}
                >
                  <SelectTrigger className="w-[130px]">
                    <SelectValue placeholder="وضعیت" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_ALL_VALUE}>همه</SelectItem>
                    {statuses.map((s) => (
                      <SelectItem key={s.slug} value={s.slug}>{s.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Select
                  value={priorityFilter === "" ? SELECT_ALL_VALUE : priorityFilter}
                  onValueChange={(v) => setPriorityFilter(v === SELECT_ALL_VALUE ? "" : v)}
                >
                  <SelectTrigger className="w-[120px]">
                    <SelectValue placeholder="اولویت" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_ALL_VALUE}>همه</SelectItem>
                    {priorities.map((p) => (
                      <SelectItem key={p.term_id} value={p.slug}>{p.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Select
                  value={departmentFilter === "" ? SELECT_ALL_VALUE : departmentFilter}
                  onValueChange={(v) => setDepartmentFilter(v === SELECT_ALL_VALUE ? "" : v)}
                >
                  <SelectTrigger className="w-[140px]">
                    <SelectValue placeholder="دپارتمان" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_ALL_VALUE}>همه</SelectItem>
                    {departments.map((d) => (
                      <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Button onClick={loadList}>فیلتر</Button>
              </div>
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : tickets.length === 0 ? (
                <div className="py-12 text-center text-muted-foreground">
                  هیچ تیکتی یافت نشد.
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>موضوع</TableHead>
                      <TableHead>آخرین بروزرسانی</TableHead>
                      <TableHead>اولویت</TableHead>
                      <TableHead>وضعیت</TableHead>
                      <TableHead></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {tickets.map((t) => (
                      <TableRow key={t.id}>
                        <TableCell>
                          <button
                            type="button"
                            className="text-primary hover:underline text-start font-medium"
                            onClick={() => openTicket(t)}
                          >
                            {t.title}
                          </button>
                        </TableCell>
                        <TableCell>{t.modified}</TableCell>
                        <TableCell><Badge variant="outline">{t.priority_name}</Badge></TableCell>
                        <TableCell><Badge variant="secondary">{t.status_name}</Badge></TableCell>
                        <TableCell>
                          <Button variant="outline" size="sm" onClick={() => openTicket(t)}>
                            مشاهده
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
          {totalPages > 1 && (
            <div className="flex justify-center gap-2">
              <Button variant="outline" size="sm" disabled={currentPage <= 1} onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}>
                قبلی
              </Button>
              <span className="flex items-center px-2 text-sm">صفحه {currentPage} از {totalPages}</span>
              <Button variant="outline" size="sm" disabled={currentPage >= totalPages} onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}>
                بعدی
              </Button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
