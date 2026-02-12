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
import { getLeads, addLead, type Lead, type GetLeadsResponse } from "@/api/leads"
import { Plus, Loader2 } from "lucide-react"

export function LeadsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [leads, setLeads] = useState<Lead[]>([])
  const [total, setTotal] = useState(0)
  const [statuses, setStatuses] = useState<{ slug: string; name: string }[]>([])
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
  })

  const load = async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getLeads({ paged: 1, per_page: 20 })
      if (res.success && res.data) {
        const d = res.data as GetLeadsResponse
        setLeads(d.leads)
        setTotal(d.total)
        setStatuses(d.statuses ?? [])
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
  }, [])

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
    })
    setSubmitting(false)
    if (res.success) {
      setOpenAdd(false)
      setForm({ first_name: "", last_name: "", mobile: "", email: "", business_name: "", gender: "", notes: "" })
      load()
    } else {
      setError(res.message ?? "خطا در ثبت سرنخ")
    }
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
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
          <CardTitle>لیست سرنخ‌ها {total > 0 ? `(${total})` : ""}{statuses.length > 0 ? ` · ${statuses.length} وضعیت` : ""}</CardTitle>
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
                    <TableHead>وضعیت</TableHead>
                    <TableHead>تاریخ</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {leads.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                        سرنخی یافت نشد.
                      </TableCell>
                    </TableRow>
                  ) : (
                    leads.map((lead) => (
                      <TableRow key={lead.id}>
                        <TableCell className="font-medium">{lead.first_name} {lead.last_name}</TableCell>
                        <TableCell dir="ltr">{lead.mobile}</TableCell>
                        <TableCell dir="ltr">{lead.email || "—"}</TableCell>
                        <TableCell>
                          <Badge variant="secondary">{lead.status_name}</Badge>
                        </TableCell>
                        <TableCell>{lead.date}</TableCell>
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
    </div>
  )
}
