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
import { getConfigOrNull } from "@/api/client"
import {
  accountingCashAccountsList,
  accountingCashAccountGet,
  accountingCashAccountSave,
  accountingCashAccountDelete,
} from "@/api/accounting"
import type { CashAccount } from "@/api/accounting"
import { Wallet, Loader2, Plus, Pencil, Trash2 } from "lucide-react"

const TYPE_LABELS: Record<string, string> = {
  bank: "بانک",
  cash: "صندوق",
  petty: "تنخواه",
}

export function CashAccountsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<CashAccount[]>([])
  const [loading, setLoading] = useState(true)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<Partial<CashAccount> & { name: string }>({
    name: "",
    type: "bank",
    code: "",
    description: "",
    card_no: "",
    sheba: "",
    is_active: 1,
    sort_order: 0,
  })
  const [deleteId, setDeleteId] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    accountingCashAccountsList().then((res) => {
      if (res.success && res.data?.items) setItems(res.data.items)
      setLoading(false)
    })
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const openCreate = () => {
    setEditingId(null)
    setForm({
      name: "",
      type: "bank",
      code: "",
      description: "",
      card_no: "",
      sheba: "",
      is_active: 1,
      sort_order: 0,
    })
    setDialogOpen(true)
  }

  const openEdit = (id: number) => {
    accountingCashAccountGet(id).then((res) => {
      if (res.success && res.data?.cash_account) {
        const c = res.data.cash_account
        setForm({
          id: c.id,
          name: c.name,
          type: c.type,
          code: c.code ?? "",
          description: c.description ?? "",
          card_no: c.card_no ?? "",
          sheba: c.sheba ?? "",
          chart_account_id: c.chart_account_id ?? undefined,
          is_active: c.is_active,
          sort_order: c.sort_order,
        })
        setEditingId(id)
        setDialogOpen(true)
      }
    })
  }

  const handleSave = () => {
    if (!form.name?.trim()) return
    setSaving(true)
    accountingCashAccountSave(form)
      .then((res) => {
        if (res?.success) {
          setDialogOpen(false)
          load()
        }
      })
      .finally(() => setSaving(false))
  }

  const handleDelete = () => {
    if (!deleteId) return
    accountingCashAccountDelete(deleteId).then((res) => {
      if (res?.success) {
        setDeleteId(null)
        load()
      }
    })
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <Wallet className="h-5 w-5" />
            حساب‌های صندوق / بانک / تنخواه
          </CardTitle>
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4 ml-1" />
            حساب جدید
          </Button>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground py-4">حسابی تعریف نشده است.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>نام</TableHead>
                  <TableHead>نوع</TableHead>
                  <TableHead>کد</TableHead>
                  <TableHead>شبا / کارت</TableHead>
                  <TableHead>وضعیت</TableHead>
                  <TableHead className="w-[100px]">عملیات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell className="font-medium">{row.name}</TableCell>
                    <TableCell>{TYPE_LABELS[row.type] ?? row.type}</TableCell>
                    <TableCell>{row.code ?? "—"}</TableCell>
                    <TableCell className="text-muted-foreground text-sm">
                      {row.sheba || row.card_no || "—"}
                    </TableCell>
                    <TableCell>{row.is_active ? "فعال" : "غیرفعال"}</TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        <Button variant="ghost" size="icon" onClick={() => openEdit(row.id)} title="ویرایش">
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" onClick={() => setDeleteId(row.id)} title="حذف">
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-md" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>{editingId ? "ویرایش حساب" : "حساب جدید"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">نام *</label>
                <Input
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  placeholder="مثلاً صندوق اصلی"
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">نوع</label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value as CashAccount["type"] }))}
                >
                  <option value="bank">بانک</option>
                  <option value="cash">صندوق</option>
                  <option value="petty">تنخواه</option>
                </select>
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">کد</label>
              <Input
                value={form.code ?? ""}
                onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))}
                placeholder="اختیاری"
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">شماره شبا</label>
              <Input
                value={form.sheba ?? ""}
                onChange={(e) => setForm((f) => ({ ...f, sheba: e.target.value }))}
                placeholder="IR..."
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">شماره کارت</label>
              <Input
                value={form.card_no ?? ""}
                onChange={(e) => setForm((f) => ({ ...f, card_no: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">توضیحات</label>
              <Input
                value={form.description ?? ""}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
              />
            </div>
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="cash_active"
                checked={!!form.is_active}
                onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked ? 1 : 0 }))}
              />
              <label htmlFor="cash_active" className="text-sm">فعال</label>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>انصراف</Button>
            <Button onClick={handleSave} disabled={saving || !form.name?.trim()}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : "ذخیره"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف حساب</AlertDialogTitle>
            <AlertDialogDescription>
              در صورت استفاده در رسید/پرداخت، حذف امکان‌پذیر نیست. آیا مطمئن هستید؟
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>انصراف</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground">
              حذف
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
