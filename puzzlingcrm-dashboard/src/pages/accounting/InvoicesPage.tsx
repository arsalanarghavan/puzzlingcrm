import { useState, useEffect, useCallback } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { getConfigOrNull } from "@/api/client"
import {
  accountingInvoiceList,
  accountingInvoiceGet,
  accountingInvoiceSave,
  accountingInvoiceDelete,
  accountingInvoiceNextNumber,
  accountingInvoiceConfirm,
  accountingFiscalYears,
  accountingPersonsList,
  accountingProductsList,
  accountingUserDefaultsGet,
} from "@/api/accounting"
import type { AccountingInvoice, AccountingInvoiceLine, FiscalYear, Person, Product } from "@/api/accounting"
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
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
import { FileText, Loader2, Plus, Pencil, Trash2, CheckCircle } from "lucide-react"

const INVOICE_TYPES = [
  { value: "proforma", label: "پیش‌فاکتور" },
  { value: "sales", label: "فروش" },
  { value: "purchase", label: "خرید" },
]
const STATUS_LABELS: Record<string, string> = {
  draft: "پیش‌نویس",
  confirmed: "تأیید شده",
  returned: "مرجوع",
}

export function InvoicesPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<AccountingInvoice[]>([])
  const [total, setTotal] = useState(0)
  const [fiscalYears, setFiscalYears] = useState<FiscalYear[]>([])
  const [persons, setPersons] = useState<Person[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const perPage = 15
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<{
    person_id: number
    invoice_date: string
    due_date?: string
    invoice_no?: string
    invoice_type: string
    lines: AccountingInvoiceLine[]
  }>({
    person_id: 0,
    invoice_date: new Date().toISOString().slice(0, 10),
    invoice_type: "sales",
    lines: [],
  })
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const [confirmingId, setConfirmingId] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    const params: Record<string, string | number> = { page, per_page: perPage }
    if (fiscalYearId > 0) params.fiscal_year_id = fiscalYearId
    accountingInvoiceList(params).then((res) => {
      if (res.success && res.data) {
        setItems(res.data.items ?? [])
        setTotal(res.data.total ?? 0)
      }
      setLoading(false)
    })
  }, [page, fiscalYearId])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    accountingFiscalYears().then((res) => {
      if (res.success && res.data?.items) {
        setFiscalYears(res.data.items)
        const active = res.data.items.find((y: FiscalYear) => y.is_active === 1)
        if (active && !fiscalYearId) setFiscalYearId(active.id)
      }
    })
    accountingPersonsList({ per_page: 500 }).then((res) => {
      if (res.success && res.data?.items) setPersons(res.data.items)
    })
    accountingProductsList({ per_page: 500 }).then((res) => {
      if (res.success && res.data?.items) setProducts(res.data.items)
    })
  }, [])

  const openCreate = () => {
    setEditingId(null)
    accountingUserDefaultsGet().then((res) => {
      const defPerson = res.success && res.data?.defaults?.default_invoice_person_id ? res.data.defaults.default_invoice_person_id : 0
      accountingInvoiceNextNumber(fiscalYearId || undefined, "sales").then((nr) => {
        const nextNo = nr.success && nr.data?.invoice_no ? nr.data.invoice_no : "sales-1"
        setForm({
          person_id: defPerson || 0,
          invoice_date: new Date().toISOString().slice(0, 10),
          invoice_type: "sales",
          invoice_no: nextNo,
          lines: [],
        })
        setDialogOpen(true)
      })
    })
  }

  const openEdit = (id: number) => {
    accountingInvoiceGet(id).then((res) => {
      if (res.success && res.data?.invoice) {
        const inv = res.data.invoice
        setForm({
          person_id: inv.person_id,
          invoice_date: inv.invoice_date,
          due_date: inv.due_date ?? undefined,
          invoice_no: inv.invoice_no,
          invoice_type: inv.invoice_type,
          lines: (res.data.lines ?? []).map((l: AccountingInvoiceLine) => ({
            product_id: l.product_id,
            quantity: l.quantity,
            unit_id: l.unit_id,
            unit_price: l.unit_price,
            discount_percent: l.discount_percent,
            discount_amount: l.discount_amount,
            tax_percent: l.tax_percent,
            tax_amount: l.tax_amount,
            description: l.description,
          })),
        })
        setEditingId(id)
        setDialogOpen(true)
      }
    })
  }

  const handleSave = () => {
    if (!form.person_id || !form.invoice_date) return
    setSaving(true)
    const payload = {
      ...form,
      id: editingId ?? undefined,
      fiscal_year_id: fiscalYearId || undefined,
      lines: form.lines.filter((l) => l.product_id > 0),
    }
    accountingInvoiceSave(payload).then((res) => {
      setSaving(false)
      if (res.success) {
        setDialogOpen(false)
        load()
      }
    })
  }

  const handleDelete = (id: number) => {
    accountingInvoiceDelete(id).then((res) => {
      if (res.success) {
        setDeleteId(null)
        load()
      }
    })
  }

  const addLine = () => {
    setForm((f) => ({
      ...f,
      lines: [...f.lines, { product_id: 0, quantity: 1, unit_price: 0 }],
    }))
  }
  const updateLine = (index: number, field: keyof AccountingInvoiceLine, value: number | string | null) => {
    setForm((f) => {
      const next = [...f.lines]
      if (!next[index]) return f
      next[index] = { ...next[index], [field]: value }
      return { ...f, lines: next }
    })
  }
  const removeLine = (index: number) => {
    setForm((f) => ({ ...f, lines: f.lines.filter((_, i) => i !== index) }))
  }

  const personName = (id: number) => persons.find((p) => p.id === id)?.name ?? id

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
            <FileText className="h-5 w-5" />
            فاکتورها
          </h2>
          <p className="text-muted-foreground text-sm">فاکتور فروش و خرید و پیش‌فاکتور</p>
        </div>
        <Button onClick={openCreate} disabled={fiscalYearId <= 0}>
          <Plus className="h-4 w-4 ml-2" />
          فاکتور جدید
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-wrap items-center gap-4">
            <CardTitle className="text-base">لیست فاکتورها</CardTitle>
            <select
              className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={fiscalYearId}
              onChange={(e) => setFiscalYearId(Number(e.target.value))}
            >
              <option value={0}>همه سال‌های مالی</option>
              {fiscalYears.map((y) => (
                <option key={y.id} value={y.id}>
                  {y.name}
                </option>
              ))}
            </select>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center gap-2 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              در حال بارگذاری...
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground">فاکتوری یافت نشد.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>شماره</TableHead>
                    <TableHead>نوع</TableHead>
                    <TableHead>طرف حساب</TableHead>
                    <TableHead>تاریخ</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead className="w-[100px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="font-mono">{row.invoice_no}</TableCell>
                      <TableCell>{INVOICE_TYPES.find((t) => t.value === row.invoice_type)?.label ?? row.invoice_type}</TableCell>
                      <TableCell>{personName(row.person_id)}</TableCell>
                      <TableCell>{row.invoice_date}</TableCell>
                      <TableCell>
                        <Badge variant={row.status === "confirmed" ? "default" : "secondary"}>{STATUS_LABELS[row.status] ?? row.status}</Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-1">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(row.id)} title="ویرایش">
                            <Pencil className="h-4 w-4" />
                          </Button>
                          {row.status === "draft" && (
                            <>
                              <Button
                                variant="ghost"
                                size="icon"
                                title="تأیید فاکتور"
                                disabled={confirmingId === row.id}
                                onClick={() => {
                                  setConfirmingId(row.id)
                                  accountingInvoiceConfirm(row.id).then((res) => {
                                    setConfirmingId(null)
                                    if (res?.success) load()
                                  }).finally(() => setConfirmingId(null))
                                }}
                              >
                                {confirmingId === row.id ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle className="h-4 w-4 text-green-600" />}
                              </Button>
                              <Button variant="ghost" size="icon" onClick={() => setDeleteId(row.id)} title="حذف">
                                <Trash2 className="h-4 w-4 text-destructive" />
                              </Button>
                            </>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {total > perPage && (
                <div className="flex justify-between mt-4">
                  <p className="text-sm text-muted-foreground">
                    {total} فاکتور — صفحه {page} از {Math.ceil(total / perPage)}
                  </p>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                      قبلی
                    </Button>
                    <Button variant="outline" size="sm" disabled={page >= Math.ceil(total / perPage)} onClick={() => setPage((p) => p + 1)}>
                      بعدی
                    </Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>{editingId ? "ویرایش فاکتور" : "فاکتور جدید"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">طرف حساب *</label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                  value={form.person_id}
                  onChange={(e) => setForm((f) => ({ ...f, person_id: Number(e.target.value) }))}
                >
                  <option value={0}>انتخاب...</option>
                  {persons.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">شماره فاکتور</label>
                <Input
                  value={form.invoice_no ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, invoice_no: e.target.value }))}
                  placeholder="sales-1"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">تاریخ *</label>
                <Input type="date" value={form.invoice_date} onChange={(e) => setForm((f) => ({ ...f, invoice_date: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">سررسید</label>
                <Input type="date" value={form.due_date ?? ""} onChange={(e) => setForm((f) => ({ ...f, due_date: e.target.value || undefined }))} />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">نوع فاکتور</label>
              <select
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={form.invoice_type}
                onChange={(e) => setForm((f) => ({ ...f, invoice_type: e.target.value }))}
              >
                {INVOICE_TYPES.map((t) => (
                  <option key={t.value} value={t.value}>
                    {t.label}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <div className="flex justify-between items-center mb-2">
                <label className="text-sm font-medium">ردیف‌ها</label>
                <Button type="button" variant="outline" size="sm" onClick={addLine}>
                  افزودن ردیف
                </Button>
              </div>
              <div className="border rounded-md overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>کالا/خدمات</TableHead>
                      <TableHead>تعداد</TableHead>
                      <TableHead>قیمت واحد</TableHead>
                      <TableHead></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {form.lines.map((line, idx) => (
                      <TableRow key={idx}>
                        <TableCell>
                          <select
                            className="flex h-8 w-full rounded border bg-transparent px-2 text-sm"
                            value={line.product_id}
                            onChange={(e) => updateLine(idx, "product_id", Number(e.target.value))}
                          >
                            <option value={0}>انتخاب...</option>
                            {products.map((p) => (
                              <option key={p.id} value={p.id}>
                                {p.code} - {p.name}
                              </option>
                            ))}
                          </select>
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            min={0.0001}
                            step={0.01}
                            className="w-24"
                            value={line.quantity}
                            onChange={(e) => updateLine(idx, "quantity", parseFloat(e.target.value) || 0)}
                          />
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            min={0}
                            step={0.01}
                            className="w-32"
                            value={line.unit_price}
                            onChange={(e) => updateLine(idx, "unit_price", parseFloat(e.target.value) || 0)}
                          />
                        </TableCell>
                        <TableCell>
                          <Button type="button" variant="ghost" size="icon" onClick={() => removeLine(idx)}>
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              انصراف
            </Button>
            <Button onClick={handleSave} disabled={saving || !form.person_id || !form.invoice_date}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={deleteId !== null} onOpenChange={(open) => !open && setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف فاکتور</AlertDialogTitle>
            <AlertDialogDescription>فقط فاکتورهای پیش‌نویس قابل حذف هستند. آیا مطمئن هستید؟</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>انصراف</AlertDialogCancel>
            <AlertDialogAction onClick={() => deleteId && handleDelete(deleteId)} className="bg-destructive text-destructive-foreground">
              حذف
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
