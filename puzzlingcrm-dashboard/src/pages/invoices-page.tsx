import { useCallback, useEffect, useState } from "react"
import { useNavigate, useSearchParams } from "react-router-dom"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
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
import { getConfigOrNull } from "@/api/client"
import {
  getInvoices,
  getInvoice,
  getProjectsForCustomer,
  manageInvoice,
  type Invoice,
  type InvoiceDetail,
} from "@/api/invoices"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Pencil, List } from "lucide-react"

function formatNumber(n: number) {
  return new Intl.NumberFormat("fa-IR").format(n)
}

export function InvoicesPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const initialTab = searchParams.get("action") === "new" ? "new" : "list"
  const editId = searchParams.get("invoice_id") ? parseInt(searchParams.get("invoice_id")!, 10) : null

  const [tab, setTab] = useState<"list" | "new">(initialTab)
  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [customers, setCustomers] = useState<{ id: number; display_name: string }[]>([])
  const [projects, setProjects] = useState<{ id: number; title: string }[]>([])
  const [totalPages, setTotalPages] = useState(1)
  const [currentPage, setCurrentPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [editingInvoice, setEditingInvoice] = useState<InvoiceDetail | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    customer_id: 0,
    project_id: 0,
    issue_date: new Date().toISOString().slice(0, 10),
    items: [] as { title: string; desc: string; price: string; discount: string }[],
    payment_method: "",
    notes: "",
  })

  const loadList = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getInvoices(currentPage)
      if (res.success && res.data) {
        setInvoices(res.data.invoices ?? [])
        setCustomers(res.data.customers ?? [])
        setTotalPages(res.data.total_pages ?? 1)
      } else {
        setError(res.message ?? "خطا در بارگذاری پیش‌فاکتورها")
      }
    } catch {
      setError("خطا در بارگذاری پیش‌فاکتورها")
    } finally {
      setLoading(false)
    }
  }, [currentPage])

  const loadInvoice = useCallback(async (id: number) => {
    setLoading(true)
    setError(null)
    try {
      const res = await getInvoice(id)
      if (res.success && res.data) {
        const inv = res.data.invoice
        if (inv) {
          setEditingInvoice(inv)
          setForm({
            customer_id: inv.customer_id,
            project_id: inv.project_id,
            issue_date: inv.issue_date ? inv.issue_date.slice(0, 10) : "",
            items: (inv.items ?? []).map((i) => ({
              title: i.title ?? "",
              desc: i.desc ?? "",
              price: String(i.price ?? 0),
              discount: String(i.discount ?? 0),
            })),
            payment_method: inv.payment_method ?? "",
            notes: inv.notes ?? "",
          })
          loadProjectsForCustomer(inv.customer_id)
        } else {
          setEditingInvoice(null)
          setError(res.message ?? "خطا در بارگذاری پیش‌فاکتور")
        }
      } else {
        setError(res.message ?? "خطا در بارگذاری پیش‌فاکتور")
      }
    } catch {
      setError("خطا در بارگذاری پیش‌فاکتور")
    } finally {
      setLoading(false)
    }
  }, [])

  const loadProjectsForCustomer = async (customerId: number) => {
    if (!customerId) {
      setProjects([])
      return
    }
    const res = await getProjectsForCustomer(customerId)
    if (res.success && res.data) {
      setProjects(Array.isArray(res.data) ? res.data : [])
    } else {
      setProjects([])
    }
  }

  useEffect(() => {
    if (tab === "list") loadList()
  }, [tab, loadList])

  useEffect(() => {
    if (editId && tab === "new") {
      loadInvoice(editId)
    } else if (tab === "new" && !editId) {
      setEditingInvoice(null)
      setForm({
        customer_id: 0,
        project_id: 0,
        issue_date: new Date().toISOString().slice(0, 10),
        items: [{ title: "", desc: "", price: "", discount: "0" }],
        payment_method: "",
        notes: "",
      })
      setProjects([])
      getInvoices(1).then((res) => {
        if (res.success && res.data) setCustomers(res.data.customers ?? [])
      })
    }
  }, [tab, editId])

  const openNew = () => {
    navigate("/invoices?action=new")
    setTab("new")
    setEditingInvoice(null)
  }

  const openEdit = (inv: Invoice) => {
    navigate(`/invoices?action=new&invoice_id=${inv.id}`)
    setTab("new")
    loadInvoice(inv.id)
  }

  const backToList = () => {
    navigate("/invoices")
    setTab("list")
    loadList()
  }

  const onCustomerChange = (customerId: number) => {
    setForm((f) => ({ ...f, customer_id: customerId, project_id: 0 }))
    loadProjectsForCustomer(customerId)
  }

  const addItemRow = () => {
    setForm((f) => ({
      ...f,
      items: [...f.items, { title: "", desc: "", price: "", discount: "0" }],
    }))
  }

  const removeItemRow = (idx: number) => {
    setForm((f) => ({
      ...f,
      items: f.items.filter((_, i) => i !== idx),
    }))
  }

  const updateItem = (idx: number, field: "title" | "desc" | "price" | "discount", value: string) => {
    setForm((f) => {
      const next = [...f.items]
      next[idx] = { ...next[idx], [field]: value }
      return { ...f, items: next }
    })
  }

  const subtotal = form.items.reduce(
    (s, i) => s + (parseFloat(i.price.replace(/\D/g, "")) || 0),
    0
  )
  const totalDiscount = form.items.reduce(
    (s, i) => s + (parseFloat(i.discount.replace(/\D/g, "")) || 0),
    0
  )
  const finalTotal = subtotal - totalDiscount

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.customer_id || !form.project_id || !form.issue_date) {
      setError("مشتری، پروژه و تاریخ صدور الزامی هستند.")
      return
    }
    const validItems = form.items.filter((i) => i.title.trim())
    if (validItems.length === 0) {
      setError("حداقل یک آیتم باید ثبت شود.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await manageInvoice({
        invoice_id: editingInvoice?.id,
        customer_id: form.customer_id,
        project_id: form.project_id,
        issue_date: form.issue_date,
        item_title: validItems.map((i) => i.title),
        item_desc: validItems.map((i) => i.desc),
        item_price: validItems.map((i) => i.price.replace(/\D/g, "") || "0"),
        item_discount: validItems.map((i) => i.discount.replace(/\D/g, "") || "0"),
        payment_method: form.payment_method,
        notes: form.notes,
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

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">پیش‌فاکتورها</h1>
        <div className="flex gap-2">
          <Button variant={tab === "list" ? "default" : "outline"} onClick={() => setTab("list")}>
            <List className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            لیست
          </Button>
          <Button variant={tab === "new" ? "default" : "outline"} onClick={openNew}>
            <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            ایجاد جدید
          </Button>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive">
          {error}
        </div>
      )}

      {tab === "list" ? (
        <>
          <Card>
            <CardContent className="p-0">
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : invoices.length === 0 ? (
                <div className="py-12 text-center text-muted-foreground">
                  هیچ پیش‌فاکتوری یافت نشد.
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>شماره</TableHead>
                      <TableHead>مشتری</TableHead>
                      <TableHead>پروژه</TableHead>
                      <TableHead>مبلغ نهایی</TableHead>
                      <TableHead>تاریخ</TableHead>
                      <TableHead>عملیات</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {invoices.map((inv) => (
                      <TableRow key={inv.id}>
                        <TableCell className="font-mono">{inv.invoice_number}</TableCell>
                        <TableCell>{inv.customer_name}</TableCell>
                        <TableCell>{inv.project_title}</TableCell>
                        <TableCell dir="ltr">{formatNumber(inv.final_total)} تومان</TableCell>
                        <TableCell>{inv.date_display}</TableCell>
                        <TableCell>
                          <Button variant="outline" size="sm" onClick={() => openEdit(inv)}>
                            <Pencil className="h-4 w-4" />
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
              <span className="flex items-center px-2 text-sm">
                صفحه {currentPage} از {totalPages}
              </span>
              <Button variant="outline" size="sm" disabled={currentPage >= totalPages} onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}>
                بعدی
              </Button>
            </div>
          )}
        </>
      ) : (
        <Card>
          <CardContent className="pt-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold">
                {editingInvoice ? "ویرایش پیش‌فاکتور" : "ایجاد پیش‌فاکتور جدید"}
              </h3>
              <Button variant="outline" size="sm" onClick={backToList}>
                بازگشت به لیست
              </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>مشتری *</Label>
                  <Select
                    value={form.customer_id ? String(form.customer_id) : ""}
                    onValueChange={(v) => onCustomerChange(parseInt(v, 10) || 0)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="انتخاب مشتری" />
                    </SelectTrigger>
                    <SelectContent>
                      {customers.map((c) => (
                        <SelectItem key={c.id} value={String(c.id)}>
                          {c.display_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>پروژه *</Label>
                  <Select
                    value={form.project_id ? String(form.project_id) : ""}
                    onValueChange={(v) => setForm((f) => ({ ...f, project_id: parseInt(v, 10) || 0 }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={form.customer_id ? "انتخاب پروژه" : "ابتدا مشتری را انتخاب کنید"} />
                    </SelectTrigger>
                    <SelectContent>
                      {projects.map((p) => (
                        <SelectItem key={p.id} value={String(p.id)}>
                          {p.title}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>تاریخ صدور *</Label>
                  <Input
                    type="date"
                    value={form.issue_date}
                    onChange={(e) => setForm((f) => ({ ...f, issue_date: e.target.value }))}
                    required
                  />
                </div>
              </div>

              <div className="space-y-4">
                <div className="flex justify-between items-center">
                  <Label>ردیف‌های خدمات</Label>
                  <Button type="button" variant="outline" size="sm" onClick={addItemRow}>
                    افزودن ردیف
                  </Button>
                </div>
                <div className="space-y-2">
                  {form.items.map((row, idx) => (
                    <div key={idx} className="grid grid-cols-12 gap-2 items-end">
                      <div className="col-span-3">
                        <Label>عنوان</Label>
                        <Input
                          value={row.title}
                          onChange={(e) => updateItem(idx, "title", e.target.value)}
                          placeholder="عنوان خدمت"
                        />
                      </div>
                      <div className="col-span-3">
                        <Label>توضیحات</Label>
                        <Input
                          value={row.desc}
                          onChange={(e) => updateItem(idx, "desc", e.target.value)}
                          placeholder="توضیحات"
                        />
                      </div>
                      <div className="col-span-2">
                        <Label>قیمت (تومان)</Label>
                        <Input
                          value={row.price}
                          onChange={(e) => updateItem(idx, "price", e.target.value)}
                          placeholder="0"
                        />
                      </div>
                      <div className="col-span-2">
                        <Label>تخفیف</Label>
                        <Input
                          value={row.discount}
                          onChange={(e) => updateItem(idx, "discount", e.target.value)}
                          placeholder="0"
                        />
                      </div>
                      <div className="col-span-1">
                        <Button type="button" variant="ghost" size="icon" onClick={() => removeItemRow(idx)} disabled={form.items.length <= 1}>
                          ×
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="flex gap-4 text-sm">
                  <span>جمع کل: <strong dir="ltr">{formatNumber(subtotal)}</strong> تومان</span>
                  <span>مبلغ نهایی: <strong dir="ltr">{formatNumber(finalTotal)}</strong> تومان</span>
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>نحوه پرداخت</Label>
                  <textarea
                    className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    value={form.payment_method}
                    onChange={(e) => setForm((f) => ({ ...f, payment_method: e.target.value }))}
                    rows={4}
                  />
                </div>
                <div className="space-y-2">
                  <Label>یادداشت‌ها</Label>
                  <textarea
                    className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    value={form.notes}
                    onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
                    rows={4}
                  />
                </div>
              </div>

              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className="h-4 w-4 animate-spin me-2" />}
                {editingInvoice ? "ذخیره" : "ایجاد و ارسال"}
              </Button>
            </form>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
