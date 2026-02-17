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
  accountingCheckList,
  accountingCheckGet,
  accountingCheckSave,
  accountingCheckDelete,
  accountingCheckSetStatus,
  accountingCashAccountsList,
  accountingPersonsList,
} from "@/api/accounting"
import type { AccountingCheck, CashAccount, Person } from "@/api/accounting"
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
import { Banknote, Loader2, Plus, Pencil, Trash2 } from "lucide-react"

const TYPE_LABELS: Record<string, string> = {
  receivable: "دریافتی",
  payable: "پرداختی",
}
const STATUS_LABELS: Record<string, string> = {
  in_safe: "در صندوق",
  collected: "وصول شده",
  returned: "برگشتی",
  spent: "خرج‌شده",
}

export function ChecksPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<AccountingCheck[]>([])
  const [total, setTotal] = useState(0)
  const [cashAccounts, setCashAccounts] = useState<CashAccount[]>([])
  const [persons, setPersons] = useState<Person[]>([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const perPage = 15
  const [typeFilter, setTypeFilter] = useState<string>("")
  const [statusFilter, setStatusFilter] = useState<string>("")
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<{
    type: string
    check_no: string
    check_date: string
    amount: number
    cash_account_id: number
    person_id: number
    due_date: string
    description: string
  }>({
    type: "receivable",
    check_no: "",
    check_date: "",
    amount: 0,
    cash_account_id: 0,
    person_id: 0,
    due_date: "",
    description: "",
  })
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const [statusChangingId, setStatusChangingId] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    const params: Record<string, string | number> = { page, per_page: perPage }
    if (typeFilter) params.type = typeFilter
    if (statusFilter) params.status = statusFilter
    accountingCheckList(params).then((res) => {
      if (res.success && res.data) {
        setItems(res.data.items ?? [])
        setTotal(res.data.total ?? 0)
      }
      setLoading(false)
    })
  }, [page, typeFilter, statusFilter])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    accountingCashAccountsList({ type: "bank" }).then((res) => {
      if (res.success && res.data?.items) setCashAccounts(res.data.items)
    })
    accountingPersonsList({ per_page: 500 }).then((res) => {
      if (res.success && res.data?.items) setPersons(res.data.items)
    })
  }, [])

  const cashAccountName = (id: number) => cashAccounts.find((c) => c.id === id)?.name ?? id
  const personName = (id: number) => persons.find((p) => p.id === id)?.name ?? id

  const openCreate = () => {
    setEditingId(null)
    const today = new Date().toISOString().slice(0, 10)
    setForm({
      type: "receivable",
      check_no: "",
      check_date: today,
      amount: 0,
      cash_account_id: cashAccounts[0]?.id ?? 0,
      person_id: 0,
      due_date: today,
      description: "",
    })
    setDialogOpen(true)
  }

  const openEdit = (id: number) => {
    accountingCheckGet(id).then((res) => {
      if (res.success && res.data?.check) {
        const c = res.data.check
        setForm({
          type: c.type,
          check_no: c.check_no,
          check_date: c.check_date ?? "",
          amount: c.amount,
          cash_account_id: c.cash_account_id,
          person_id: c.person_id,
          due_date: c.due_date,
          description: c.description ?? "",
        })
        setEditingId(id)
        setDialogOpen(true)
      }
    })
  }

  const handleSave = () => {
    if (!form.check_no.trim() || form.cash_account_id <= 0 || form.person_id <= 0 || !form.due_date || form.amount <= 0) return
    setSaving(true)
    const payload = { ...form, id: editingId ?? undefined }
    accountingCheckSave(payload)
      .then((res) => {
        if (res?.success) {
          setDialogOpen(false)
          load()
        }
      })
      .finally(() => setSaving(false))
  }

  const handleSetStatus = (id: number, status: "collected" | "returned" | "spent") => {
    setStatusChangingId(id)
    accountingCheckSetStatus(id, status).then((res) => {
      setStatusChangingId(null)
      if (res?.success) load()
    }).finally(() => setStatusChangingId(null))
  }

  const handleDelete = () => {
    if (!deleteId) return
    accountingCheckDelete(deleteId).then((res) => {
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
            <Banknote className="h-5 w-5" />
            چک‌های دریافتی و پرداختی
          </CardTitle>
          <div className="flex gap-2 flex-wrap">
            <select
              className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
            >
              <option value="">همه انواع</option>
              <option value="receivable">دریافتی</option>
              <option value="payable">پرداختی</option>
            </select>
            <select
              className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="">همه وضعیت‌ها</option>
              <option value="in_safe">در صندوق</option>
              <option value="collected">وصول شده</option>
              <option value="returned">برگشتی</option>
              <option value="spent">خرج‌شده</option>
            </select>
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4 ml-1" />
              چک جدید
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground py-4">چکی یافت نشد.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>شماره چک</TableHead>
                    <TableHead>نوع</TableHead>
                    <TableHead>طرف حساب</TableHead>
                    <TableHead>بانک</TableHead>
                    <TableHead>مبلغ</TableHead>
                    <TableHead>سررسید</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead className="w-[140px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="font-mono">{row.check_no}</TableCell>
                      <TableCell>{TYPE_LABELS[row.type] ?? row.type}</TableCell>
                      <TableCell>{personName(row.person_id)}</TableCell>
                      <TableCell>{cashAccountName(row.cash_account_id)}</TableCell>
                      <TableCell>{Number(row.amount).toLocaleString("fa-IR")}</TableCell>
                      <TableCell>{row.due_date}</TableCell>
                      <TableCell>
                        <Badge variant={row.status === "in_safe" ? "secondary" : row.status === "returned" ? "destructive" : "default"}>
                          {STATUS_LABELS[row.status] ?? row.status}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-1 flex-wrap">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(row.id)} title="ویرایش">
                            <Pencil className="h-4 w-4" />
                          </Button>
                          {row.type === "receivable" && row.status === "in_safe" && (
                            <Button
                              variant="ghost"
                              size="sm"
                              disabled={statusChangingId === row.id}
                              onClick={() => handleSetStatus(row.id, "collected")}
                            >
                              {statusChangingId === row.id ? <Loader2 className="h-4 w-4 animate-spin" /> : "وصول"}
                            </Button>
                          )}
                          {row.type === "payable" && row.status === "in_safe" && (
                            <Button
                              variant="ghost"
                              size="sm"
                              disabled={statusChangingId === row.id}
                              onClick={() => handleSetStatus(row.id, "spent")}
                            >
                              {statusChangingId === row.id ? <Loader2 className="h-4 w-4 animate-spin" /> : "خرج"}
                            </Button>
                          )}
                          {row.status === "in_safe" && (
                            <Button
                              variant="ghost"
                              size="sm"
                              className="text-destructive"
                              disabled={statusChangingId === row.id}
                              onClick={() => handleSetStatus(row.id, "returned")}
                            >
                              برگشت
                            </Button>
                          )}
                          <Button variant="ghost" size="icon" onClick={() => setDeleteId(row.id)} title="حذف">
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {total > perPage && (
                <div className="flex justify-between mt-4">
                  <p className="text-sm text-muted-foreground">
                    {total} چک — صفحه {page} از {Math.ceil(total / perPage)}
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
        <DialogContent className="max-w-md" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>{editingId ? "ویرایش چک" : "ثبت چک جدید"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">نوع *</label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}
                >
                  <option value="receivable">دریافتی</option>
                  <option value="payable">پرداختی</option>
                </select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">شماره چک *</label>
                <Input
                  value={form.check_no}
                  onChange={(e) => setForm((f) => ({ ...f, check_no: e.target.value }))}
                  placeholder="123456"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">تاریخ چک</label>
                <Input
                  type="date"
                  value={form.check_date}
                  onChange={(e) => setForm((f) => ({ ...f, check_date: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">تاریخ سررسید *</label>
                <Input
                  type="date"
                  value={form.due_date}
                  onChange={(e) => setForm((f) => ({ ...f, due_date: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">بانک *</label>
              <select
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={form.cash_account_id}
                onChange={(e) => setForm((f) => ({ ...f, cash_account_id: Number(e.target.value) }))}
              >
                <option value={0}>انتخاب...</option>
                {cashAccounts.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">طرف حساب *</label>
              <select
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={form.person_id}
                onChange={(e) => setForm((f) => ({ ...f, person_id: Number(e.target.value) }))}
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
            <div className="space-y-2">
              <label className="text-sm font-medium">شرح</label>
              <Input
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>انصراف</Button>
            <Button
              onClick={handleSave}
              disabled={saving || !form.check_no.trim() || form.cash_account_id <= 0 || form.person_id <= 0 || !form.due_date || form.amount <= 0}
            >
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : "ذخیره"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف چک</AlertDialogTitle>
            <AlertDialogDescription>آیا از حذف این چک اطمینان دارید؟</AlertDialogDescription>
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
