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
import { Label } from "@/components/ui/label"
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
import { Badge } from "@/components/ui/badge"
import { getConfigOrNull } from "@/api/client"
import {
  accountingProductsList,
  accountingProductGet,
  accountingProductSave,
  accountingProductDelete,
  accountingProductCategories,
  accountingUnitsList,
} from "@/api/accounting"
import type { Product, ProductCategory, Unit } from "@/api/accounting"
import { Package, Loader2, Plus, Pencil, Trash2 } from "lucide-react"

export function ProductsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<Product[]>([])
  const [total, setTotal] = useState(0)
  const [categories, setCategories] = useState<ProductCategory[]>([])
  const [units, setUnits] = useState<Unit[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState("")
  const [page, setPage] = useState(1)
  const perPage = 15
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<Partial<Product> & { code: string; name: string }>({
    code: "",
    name: "",
    main_unit_id: 0,
    is_active: 1,
  })
  const [deleteId, setDeleteId] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    const params: Record<string, string | number> = { page, per_page: perPage }
    if (search.trim()) params.search = search.trim()
    accountingProductsList(params).then((res) => {
      if (res.success && res.data) {
        setItems(res.data.items ?? [])
        setTotal(res.data.total ?? 0)
      }
      setLoading(false)
    })
  }, [page, search])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    accountingProductCategories().then((res) => {
      if (res.success && res.data?.items) setCategories(res.data.items)
    })
    accountingUnitsList().then((res) => {
      if (res.success && res.data?.items) setUnits(res.data.items)
    })
  }, [])

  const openCreate = () => {
    setEditingId(null)
    const firstUnitId = units.length > 0 ? units[0].id : 0
    setForm({
      code: "",
      name: "",
      main_unit_id: firstUnitId,
      is_active: 1,
    })
    setDialogOpen(true)
  }

  const openEdit = (id: number) => {
    accountingProductGet(id).then((res) => {
      if (res.success && res.data?.product) {
        setForm(res.data.product)
        setEditingId(id)
        setDialogOpen(true)
      }
    })
  }

  const handleSave = () => {
    if (!form.code?.trim() || !form.name?.trim()) return
    setSaving(true)
    accountingProductSave({ ...form, id: editingId ?? undefined }).then((res) => {
      setSaving(false)
      if (res.success) {
        setDialogOpen(false)
        load()
      }
    })
  }

  const handleDelete = (id: number) => {
    accountingProductDelete(id).then((res) => {
      if (res.success) {
        setDeleteId(null)
        load()
      }
    })
  }

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
            <Package className="h-5 w-5" />
            کالا و خدمات
          </h2>
          <p className="text-muted-foreground text-sm">مدیریت کالاها و خدمات برای فاکتور و انبار</p>
        </div>
        <Button onClick={openCreate} disabled={units.length === 0}>
          <Plus className="h-4 w-4 ml-2" />
          کالا / خدمات جدید
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-wrap items-center gap-4">
            <CardTitle className="text-base">لیست کالا و خدمات</CardTitle>
            <Input
              placeholder="جستجو (نام، کد، بارکد...)"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && setPage(1)}
              className="max-w-xs"
            />
            <Button variant="outline" size="sm" onClick={() => setPage(1)}>
              جستجو
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center gap-2 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              در حال بارگذاری...
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground">کالا یا خدماتی یافت نشد.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>کد</TableHead>
                    <TableHead>نام</TableHead>
                    <TableHead>قیمت خرید</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead className="w-[100px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="font-mono">{row.code}</TableCell>
                      <TableCell className="font-medium">{row.name}</TableCell>
                      <TableCell>{row.purchase_price != null ? row.purchase_price.toLocaleString("fa-IR") : "—"}</TableCell>
                      <TableCell>
                        {row.is_active ? <Badge>فعال</Badge> : <Badge variant="secondary">غیرفعال</Badge>}
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-1">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(row.id)}>
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" onClick={() => setDeleteId(row.id)}>
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {total > perPage && (
                <div className="flex items-center justify-between mt-4">
                  <p className="text-sm text-muted-foreground">
                    {total} مورد — صفحه {page} از {Math.ceil(total / perPage)}
                  </p>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                      قبلی
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={page >= Math.ceil(total / perPage)}
                      onClick={() => setPage((p) => p + 1)}
                    >
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
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editingId ? "ویرایش کالا / خدمات" : "کالا / خدمات جدید"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>کد *</Label>
                <Input
                  value={form.code ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))}
                  placeholder="کد یکتا"
                />
              </div>
              <div className="space-y-2">
                <Label>نام *</Label>
                <Input
                  value={form.name ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  placeholder="نام کالا یا خدمات"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>واحد اصلی</Label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                  value={form.main_unit_id ?? 0}
                  onChange={(e) => setForm((f) => ({ ...f, main_unit_id: Number(e.target.value) }))}
                >
                  {units.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name} {u.symbol ? `(${u.symbol})` : ""}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label>دسته‌بندی</Label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                  value={form.category_id ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, category_id: e.target.value ? Number(e.target.value) : null }))}
                >
                  <option value="">بدون دسته</option>
                  {categories.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>قیمت خرید</Label>
                <Input
                  type="number"
                  min={0}
                  step={0.01}
                  value={form.purchase_price ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, purchase_price: e.target.value ? Number(e.target.value) : null }))}
                  placeholder="0"
                />
              </div>
              <div className="space-y-2">
                <Label>بارکد</Label>
                <Input
                  value={form.barcode ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, barcode: e.target.value }))}
                  placeholder="چند بارکد با ;"
                />
              </div>
            </div>
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="inventory_controlled"
                checked={!!form.inventory_controlled}
                onChange={(e) => setForm((f) => ({ ...f, inventory_controlled: e.target.checked ? 1 : 0 }))}
              />
              <Label htmlFor="inventory_controlled">کنترل موجودی</Label>
            </div>
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="is_active"
                checked={!!form.is_active}
                onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked ? 1 : 0 }))}
              />
              <Label htmlFor="is_active">فعال</Label>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              انصراف
            </Button>
            <Button onClick={handleSave} disabled={saving || !form.code?.trim() || !form.name?.trim()}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={deleteId !== null} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف کالا / خدمات</AlertDialogTitle>
            <AlertDialogDescription>آیا از حذف این مورد اطمینان دارید؟</AlertDialogDescription>
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
