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
  accountingReceiptVoucherList,
  accountingReceiptVoucherGet,
  accountingReceiptVoucherSave,
  accountingReceiptVoucherPost,
  accountingReceiptVoucherDelete,
  accountingFiscalYears,
  accountingCashAccountsList,
  accountingPersonsList,
} from "@/api/accounting"
import type { ReceiptVoucher, CashAccount, Person, FiscalYear } from "@/api/accounting"
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
import { Receipt, Loader2, Plus, Pencil, Trash2, CheckCircle } from "lucide-react"

const TYPE_LABELS: Record<string, string> = {
  receipt: "دریافت",
  payment: "پرداخت",
  transfer: "انتقال",
}
const STATUS_LABELS: Record<string, string> = {
  draft: "پیش‌نویس",
  posted: "ثبت‌شده",
}

export function ReceiptsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<ReceiptVoucher[]>([])
  const [total, setTotal] = useState(0)
  const [fiscalYears, setFiscalYears] = useState<FiscalYear[]>([])
  const [cashAccounts, setCashAccounts] = useState<CashAccount[]>([])
  const [persons, setPersons] = useState<Person[]>([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const perPage = 15
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [typeFilter, setTypeFilter] = useState<string>("")
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<{
    voucher_date: string
    type: string
    cash_account_id: number
    transfer_to_cash_account_id: number | null
    person_id: number | null
    amount: number
    description: string
    invoice_id: number | null
    project_id: number | null
    bank_fee: number | null
  }>({
    voucher_date: new Date().toISOString().slice(0, 10),
    type: "receipt",
    cash_account_id: 0,
    transfer_to_cash_account_id: null,
    person_id: null,
    amount: 0,
    description: "",
    invoice_id: null,
    project_id: null,
    bank_fee: null,
  })
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const [postingId, setPostingId] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    const params: Record<string, string | number> = { page, per_page: perPage }
    if (fiscalYearId > 0) params.fiscal_year_id = fiscalYearId
    if (typeFilter) params.type = typeFilter
    accountingReceiptVoucherList(params).then((res) => {
      if (res.success && res.data) {
        setItems(res.data.items ?? [])
        setTotal(res.data.total ?? 0)
      }
      setLoading(false)
    })
  }, [page, fiscalYearId, typeFilter])

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
    accountingCashAccountsList().then((res) => {
      if (res.success && res.data?.items) setCashAccounts(res.data.items)
    })
    accountingPersonsList({ per_page: 500 }).then((res) => {
      if (res.success && res.data?.items) setPersons(res.data.items)
    })
  }, [])

  const cashAccountName = (id: number) => cashAccounts.find((c) => c.id === id)?.name ?? id
  const personName = (id: number | null) => (id ? persons.find((p) => p.id === id)?.name ?? id : "—")

  const openCreate = () => {
    setEditingId(null)
    const today = new Date().toISOString().slice(0, 10)
    const firstCash = cashAccounts[0]?.id ?? 0
    setForm({
      voucher_date: today,
      type: "receipt",
      cash_account_id: firstCash,
      transfer_to_cash_account_id: null,
      person_id: null,
      amount: 0,
      description: "",
      invoice_id: null,
      project_id: null,
      bank_fee: null,
    })
    setDialogOpen(true)
  }

  const openEdit = (id: number) => {
    accountingReceiptVoucherGet(id).then((res) => {
      if (res.success && res.data?.voucher) {
        const v = res.data.voucher
        setForm({
          voucher_date: v.voucher_date,
          type: v.type,
          cash_account_id: v.cash_account_id,
          transfer_to_cash_account_id: v.transfer_to_cash_account_id ?? null,
          person_id: v.person_id ?? null,
          amount: v.amount,
          description: v.description ?? "",
          invoice_id: v.invoice_id ?? null,
          project_id: v.project_id ?? null,
          bank_fee: v.bank_fee ?? null,
        })
        setEditingId(id)
        setDialogOpen(true)
      }
    })
  }

  const handleSave = () => {
    if (form.cash_account_id <= 0) return
    if (form.type !== "transfer" && form.amount <= 0) return
    setSaving(true)
    const payload = {
      ...form,
      id: editingId ?? undefined,
      fiscal_year_id: fiscalYearId || undefined,
    }
    accountingReceiptVoucherSave(payload)
      .then((res) => {
        if (res?.success) {
          setDialogOpen(false)
          load()
        }
      })
      .finally(() => setSaving(false))
  }

  const handlePost = (id: number) => {
    setPostingId(id)
    accountingReceiptVoucherPost(id).then((res) => {
      setPostingId(null)
      if (res?.success) load()
    }).finally(() => setPostingId(null))
  }

  const handleDelete = () => {
    if (!deleteId) return
    accountingReceiptVoucherDelete(deleteId).then((res) => {
      if (res?.success) {
        setDeleteId(null)
        load()
      }
    })
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <Card>
        <CardHeader className="flex flex-row items-center justify-between flex-wrap gap-2">
          <CardTitle className="flex items-center gap-2">
            <Receipt className="h-5 w-5" />
            رسید و پرداخت
          </CardTitle>
          <div className="flex gap-2 flex-wrap">
            <select
              className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={fiscalYearId}
              onChange={(e) => setFiscalYearId(Number(e.target.value))}
            >
              <option value={0}>همه سال‌های مالی</option>
              {fiscalYears.map((y) => (
                <option key={y.id} value={y.id}>{y.name}</option>
              ))}
            </select>
            <select
              className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
            >
              <option value="">همه انواع</option>
              <option value="receipt">دریافت</option>
              <option value="payment">پرداخت</option>
              <option value="transfer">انتقال</option>
            </select>
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4 ml-1" />
              رسید/پرداخت جدید
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground py-4">رسید یا پرداختی یافت نشد.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>شماره</TableHead>
                    <TableHead>تاریخ</TableHead>
                    <TableHead>نوع</TableHead>
                    <TableHead>حساب</TableHead>
                    <TableHead>طرف حساب</TableHead>
                    <TableHead>مبلغ</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead className="w-[120px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="font-mono">{row.voucher_no}</TableCell>
                      <TableCell>{row.voucher_date}</TableCell>
                      <TableCell>{TYPE_LABELS[row.type] ?? row.type}</TableCell>
                      <TableCell>{cashAccountName(row.cash_account_id)}</TableCell>
                      <TableCell>
                        {row.type === "transfer"
                          ? (row.transfer_to_cash_account_id ? cashAccountName(row.transfer_to_cash_account_id) : "—")
                          : personName(row.person_id)}
                      </TableCell>
                      <TableCell>{Number(row.amount).toLocaleString("fa-IR")}</TableCell>
                      <TableCell>
                        <Badge variant={row.status === "posted" ? "default" : "secondary"}>
                          {STATUS_LABELS[row.status] ?? row.status}
                        </Badge>
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
                                title="ثبت"
                                disabled={postingId === row.id}
                                onClick={() => handlePost(row.id)}
                              >
                                {postingId === row.id ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle className="h-4 w-4 text-green-600" />}
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
                    {total} مورد — صفحه {page} از {Math.ceil(total / perPage)}
                  </p>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>قبلی</Button>
                    <Button variant="outline" size="sm" disabled={page >= Math.ceil(total / perPage)} onClick={() => setPage((p) => p + 1)}>بعدی</Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>{editingId ? "ویرایش رسید/پرداخت" : "رسید یا پرداخت جدید"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">تاریخ *</label>
                <Input
                  type="date"
                  value={form.voucher_date}
                  onChange={(e) => setForm((f) => ({ ...f, voucher_date: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">نوع *</label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}
                >
                  <option value="receipt">دریافت</option>
                  <option value="payment">پرداخت</option>
                  <option value="transfer">انتقال</option>
                </select>
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">حساب (صندوق/بانک) *</label>
              <select
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={form.cash_account_id}
                onChange={(e) => setForm((f) => ({ ...f, cash_account_id: Number(e.target.value) }))}
              >
                <option value={0}>انتخاب...</option>
                {cashAccounts.filter((c) => c.is_active).map((c) => (
                  <option key={c.id} value={c.id}>{c.name} ({TYPE_LABELS[c.type]})</option>
                ))}
              </select>
            </div>
            {form.type === "transfer" && (
              <div className="space-y-2">
                <label className="text-sm font-medium">انتقال به حساب</label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                  value={form.transfer_to_cash_account_id ?? 0}
                  onChange={(e) => setForm((f) => ({ ...f, transfer_to_cash_account_id: Number(e.target.value) || null }))}
                >
                  <option value={0}>انتخاب...</option>
                  {cashAccounts.filter((c) => c.is_active && c.id !== form.cash_account_id).map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>
            )}
            {form.type !== "transfer" && (
              <>
                <div className="space-y-2">
                  <label className="text-sm font-medium">طرف حساب</label>
                  <select
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    value={form.person_id ?? 0}
                    onChange={(e) => setForm((f) => ({ ...f, person_id: Number(e.target.value) || null }))}
                  >
                    <option value={0}>انتخاب...</option>
                    {persons.map((p) => (
                      <option key={p.id} value={p.id}>{p.name}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">مبلغ *</label>
                  <Input
                    type="number"
                    min={0}
                    step={0.01}
                    value={form.amount || ""}
                    onChange={(e) => setForm((f) => ({ ...f, amount: parseFloat(e.target.value) || 0 }))}
                  />
                </div>
              </>
            )}
            {form.type === "transfer" && (
              <div className="space-y-2">
                <label className="text-sm font-medium">مبلغ انتقال *</label>
                <Input
                  type="number"
                  min={0}
                  step={0.01}
                  value={form.amount || ""}
                  onChange={(e) => setForm((f) => ({ ...f, amount: parseFloat(e.target.value) || 0 }))}
                />
              </div>
            )}
            <div className="space-y-2">
              <label className="text-sm font-medium">شرح</label>
              <Input
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">کارمزد بانکی</label>
              <Input
                type="number"
                min={0}
                step={0.01}
                value={form.bank_fee ?? ""}
                onChange={(e) => setForm((f) => ({ ...f, bank_fee: e.target.value ? parseFloat(e.target.value) : null }))}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>انصراف</Button>
            <Button
              onClick={handleSave}
              disabled={saving || form.cash_account_id <= 0 || (form.type !== "transfer" && form.amount <= 0) || (form.type === "transfer" && form.amount <= 0)}
            >
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : "ذخیره"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف رسید/پرداخت</AlertDialogTitle>
            <AlertDialogDescription>فقط پیش‌نویس قابل حذف است. آیا مطمئن هستید؟</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>انصراف</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground">حذف</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
