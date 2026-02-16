import { useEffect, useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { getConfigOrNull } from "@/api/client"
import {
  getLeads,
  addLead,
  assignLead,
  getLeadAssignees,
  type Lead,
  type GetLeadsResponse,
} from "@/api/leads"
import { Plus, Loader2, UserPlus, Eye, FileText } from "lucide-react"
import { useNavigate } from "react-router-dom"
import { cn } from "@/lib/utils"

const STATUS_BADGE_VARIANTS: Record<string, string> = {
  new: "secondary",
  assigned: "default",
  "in-progress": "outline",
  contracted: "default",
  converted: "default",
  cancelled: "destructive",
}

function StatusBadge({ slug, name }: { slug: string; name: string }) {
  const v = STATUS_BADGE_VARIANTS[slug] ?? "secondary"
  return <Badge variant={v as "default" | "secondary" | "destructive" | "outline"}>{name}</Badge>
}

export function LeadsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const navigate = useNavigate()
  const [leads, setLeads] = useState<Lead[]>([])
  const [total, setTotal] = useState(0)
  const [statuses, setStatuses] = useState<{ slug: string; name: string }[]>([])
  const [leadSources, setLeadSources] = useState<{ slug: string; name: string }[]>([])
  const [campaigns, setCampaigns] = useState<{ id: number; title: string }[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [openAdd, setOpenAdd] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    mobile: "",
    email: "",
    business_name: "",
    gender: "",
    notes: "",
    lead_source: "",
    campaign_id: 0,
  })
  const [statusFilter, setStatusFilter] = useState<string>("")
  const [openAssign, setOpenAssign] = useState<Lead | null>(null)
  const [assignTo, setAssignTo] = useState<number>(0)
  const [assignNote, setAssignNote] = useState("")
  const [assignSubmitting, setAssignSubmitting] = useState(false)
  const [assignees, setAssignees] = useState<{ id: number; display_name: string }[]>([])
  const [assigneesLoading, setAssigneesLoading] = useState(false)
  const [openDetail, setOpenDetail] = useState<Lead | null>(null)

  const load = async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getLeads({
        paged: 1,
        per_page: 20,
        status_filter: statusFilter || undefined,
      })
      if (res.success && res.data) {
        const d = res.data as GetLeadsResponse
        setLeads(d.leads)
        setTotal(d.total)
        setStatuses(d.statuses ?? [])
        setLeadSources(d.lead_sources ?? [])
        setCampaigns(d.campaigns ?? [])
      } else {
        setError(res.message ?? "خطا در بارگذاری سرنخ‌ها")
      }
    } catch {
      setError("خطا در بارگذاری سرنخ‌ها")
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [statusFilter])

  const loadAssignees = async () => {
    setAssigneesLoading(true)
    try {
      const res = await getLeadAssignees()
      if (res.success && res.data?.users) {
        setAssignees(res.data.users)
      }
    } finally {
      setAssigneesLoading(false)
    }
  }

  useEffect(() => {
    if (openAssign) {
      loadAssignees()
      setAssignTo(openAssign.assigned_to ?? 0)
      setAssignNote("")
    }
  }, [openAssign])

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.first_name.trim() || !form.last_name.trim() || !form.mobile.trim()) {
      setError("نام، نام خانوادگی و شماره موبایل ضروری هستند.")
      return
    }
    setSubmitting(true)
    setError(null)
    const res = await addLead({
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      mobile: form.mobile.trim(),
      email: form.email.trim() || undefined,
      business_name: form.business_name.trim() || undefined,
      gender: form.gender || undefined,
      notes: form.notes.trim() || undefined,
      lead_source: form.lead_source.trim() || undefined,
      campaign_id: form.campaign_id > 0 ? form.campaign_id : undefined,
    })
    setSubmitting(false)
    if (res.success) {
      setOpenAdd(false)
      setForm({
        first_name: "",
        last_name: "",
        mobile: "",
        email: "",
        business_name: "",
        gender: "",
        notes: "",
        lead_source: "",
        campaign_id: 0,
      })
      load()
    } else {
      setError(res.message ?? "خطا در ثبت سرنخ")
    }
  }

  const handleAssign = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!openAssign) return
    setAssignSubmitting(true)
    setError(null)
    const res = await assignLead(openAssign.id, assignTo, assignNote || undefined)
    setAssignSubmitting(false)
    if (res.success) {
      setOpenAssign(null)
      load()
    } else {
      setError(res.message ?? "خطا در ارجاع")
    }
  }

  return (
    <div className={cn("space-y-4", isRtl && "text-right")} dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">سرنخ‌ها</h1>
        <Button onClick={() => setOpenAdd(true)}>
          <Plus className="h-4 w-4 ml-2" />
          افزودن سرنخ
        </Button>
      </div>
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      <Card>
        <CardHeader>
          <div className="flex flex-wrap items-center justify-between gap-4">
            <CardTitle>
              لیست سرنخ‌ها {total > 0 ? `(${total})` : ""}
              {statuses.length > 0 ? ` · ${statuses.length} وضعیت` : ""}
            </CardTitle>
            {statuses.length > 0 && (
              <Select value={statusFilter || "_all"} onValueChange={(v) => setStatusFilter(v === "_all" ? "" : v)}>
                <SelectTrigger className="w-40">
                  <SelectValue placeholder="همه وضعیت‌ها" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="_all">همه وضعیت‌ها</SelectItem>
                  {statuses.map((s) => (
                    <SelectItem key={s.slug} value={s.slug}>
                      {s.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>نام</TableHead>
                  <TableHead>موبایل</TableHead>
                  <TableHead>ایمیل</TableHead>
                  <TableHead>منبع</TableHead>
                  <TableHead>ارجاع به</TableHead>
                  <TableHead>وضعیت</TableHead>
                  <TableHead>تاریخ</TableHead>
                  <TableHead className="w-24">عملیات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {leads.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                      سرنخی یافت نشد.
                    </TableCell>
                  </TableRow>
                ) : (
                  leads.map((lead) => (
                    <TableRow key={lead.id}>
                      <TableCell className="font-medium">
                        {lead.first_name} {lead.last_name}
                      </TableCell>
                      <TableCell dir="ltr">{lead.mobile}</TableCell>
                      <TableCell dir="ltr">{lead.email || "—"}</TableCell>
                      <TableCell>{lead.lead_source_name || "—"}</TableCell>
                      <TableCell>{lead.assigned_to_name || "—"}</TableCell>
                      <TableCell>
                        <StatusBadge slug={lead.status_slug} name={lead.status_name} />
                      </TableCell>
                      <TableCell>{lead.date}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setOpenDetail(lead)}
                            title="مشاهده"
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setOpenAssign(lead)}
                            title="ارجاع"
                          >
                            <UserPlus className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigate(`/contracts?action=new&from_lead=${lead.id}`)}
                            title="ایجاد قرارداد"
                          >
                            <FileText className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={openAdd} onOpenChange={setOpenAdd}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>افزودن سرنخ</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleAdd} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="first_name">نام *</Label>
                <Input
                  id="first_name"
                  value={form.first_name}
                  onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="last_name">نام خانوادگی *</Label>
                <Input
                  id="last_name"
                  value={form.last_name}
                  onChange={(e) => setForm((f) => ({ ...f, last_name: e.target.value }))}
                  required
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="mobile">شماره موبایل *</Label>
              <Input
                id="mobile"
                type="tel"
                dir="ltr"
                value={form.mobile}
                onChange={(e) => setForm((f) => ({ ...f, mobile: e.target.value }))}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">ایمیل</Label>
              <Input
                id="email"
                type="email"
                dir="ltr"
                value={form.email}
                onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="business_name">نام کسب‌وکار</Label>
              <Input
                id="business_name"
                value={form.business_name}
                onChange={(e) => setForm((f) => ({ ...f, business_name: e.target.value }))}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>منبع ورود</Label>
                <Select
                  value={form.lead_source || "_none"}
                  onValueChange={(v) => setForm((f) => ({ ...f, lead_source: v === "_none" ? "" : v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="انتخاب کنید" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="_none">—</SelectItem>
                    {leadSources.map((s) => (
                      <SelectItem key={s.slug} value={s.slug}>
                        {s.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>کمپین</Label>
                <Select
                  value={form.campaign_id > 0 ? String(form.campaign_id) : "_none"}
                  onValueChange={(v) =>
                    setForm((f) => ({ ...f, campaign_id: v === "_none" ? 0 : parseInt(v, 10) }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="انتخاب کنید" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="_none">—</SelectItem>
                    {campaigns.map((c) => (
                      <SelectItem key={c.id} value={String(c.id)}>
                        {c.title}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-2">
              <Label>جنسیت</Label>
              <Select value={form.gender || undefined} onValueChange={(v) => setForm((f) => ({ ...f, gender: v }))}>
                <SelectTrigger>
                  <SelectValue placeholder="انتخاب کنید" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="male">آقا</SelectItem>
                  <SelectItem value="female">خانم</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="notes">یادداشت</Label>
              <Input
                id="notes"
                value={form.notes}
                onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpenAdd(false)}>
                انصراف
              </Button>
              <Button type="submit" disabled={submitting}>
                ذخیره
                {submitting ? <Loader2 className="h-4 w-4 animate-spin ml-2" /> : null}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={!!openAssign} onOpenChange={(o) => !o && setOpenAssign(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              ارجاع سرنخ
              {openAssign && (
                <span className="font-normal text-muted-foreground mr-2">
                  — {openAssign.first_name} {openAssign.last_name}
                </span>
              )}
            </DialogTitle>
          </DialogHeader>
          <form onSubmit={handleAssign} className="space-y-4">
            <div className="space-y-2">
              <Label>ارجاع به کارشناس فروش</Label>
              {assigneesLoading ? (
                <div className="flex items-center gap-2 py-2">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  <span className="text-sm text-muted-foreground">در حال بارگذاری...</span>
                </div>
              ) : (
                <Select value={assignTo > 0 ? String(assignTo) : "_none"} onValueChange={(v) => setAssignTo(v === "_none" ? 0 : parseInt(v, 10))}>
                  <SelectTrigger>
                    <SelectValue placeholder="انتخاب کنید" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="_none">بدون ارجاع</SelectItem>
                    {assignees.map((u) => (
                      <SelectItem key={u.id} value={String(u.id)}>
                        {u.display_name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="assign_note">یادداشت ارجاع</Label>
              <Input
                id="assign_note"
                value={assignNote}
                onChange={(e) => setAssignNote(e.target.value)}
                placeholder="توضیح مختصر برای ارجاع..."
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setOpenAssign(null)}>
                انصراف
              </Button>
              <Button type="submit" disabled={assignSubmitting}>
                ارجاع
                {assignSubmitting ? <Loader2 className="h-4 w-4 animate-spin ml-2" /> : null}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={!!openDetail} onOpenChange={(o) => !o && setOpenDetail(null)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>جزئیات سرنخ</DialogTitle>
          </DialogHeader>
          {openDetail && (
            <div className="space-y-3 text-sm">
              <div className="flex justify-end mb-2">
                <Button size="sm" onClick={() => { setOpenDetail(null); navigate(`/contracts?action=new&from_lead=${openDetail.id}`) }}>
                  <FileText className="h-4 w-4 ml-2" />
                  ایجاد قرارداد
                </Button>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <span className="text-muted-foreground">نام:</span>
                <span>
                  {openDetail.first_name} {openDetail.last_name}
                </span>
                <span className="text-muted-foreground">موبایل:</span>
                <span dir="ltr">{openDetail.mobile}</span>
                <span className="text-muted-foreground">ایمیل:</span>
                <span dir="ltr">{openDetail.email || "—"}</span>
                <span className="text-muted-foreground">نام کسب‌وکار:</span>
                <span>{openDetail.business_name || "—"}</span>
                <span className="text-muted-foreground">منبع ورود:</span>
                <span>{openDetail.lead_source_name || "—"}</span>
                <span className="text-muted-foreground">ارجاع به:</span>
                <span>{openDetail.assigned_to_name || "—"}</span>
                <span className="text-muted-foreground">وضعیت:</span>
                <span>
                  <StatusBadge slug={openDetail.status_slug} name={openDetail.status_name} />
                </span>
                <span className="text-muted-foreground">تاریخ:</span>
                <span>{openDetail.date}</span>
                {openDetail.last_assignment_note ? (
                  <>
                    <span className="text-muted-foreground">یادداشت ارجاع:</span>
                    <span>{openDetail.last_assignment_note}</span>
                  </>
                ) : null}
                {openDetail.notes ? (
                  <>
                    <span className="text-muted-foreground">یادداشت:</span>
                    <span className="whitespace-pre-wrap">{openDetail.notes}</span>
                  </>
                ) : null}
              </div>
              {openDetail.form_submission_data && openDetail.form_submission_data.length > 0 ? (
                <div className="mt-4 pt-4 border-t">
                  <h4 className="font-medium mb-2">داده‌های فرم</h4>
                  <div className="space-y-2 text-sm">
                    {openDetail.form_submission_data.map((f, i) => (
                      <div key={i} className="flex gap-2">
                        <span className="text-muted-foreground shrink-0 min-w-24">{f.label}:</span>
                        <span className="whitespace-pre-wrap break-words">{f.value || "—"}</span>
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}
