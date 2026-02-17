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
  accountingPersonsList,
  accountingPersonGet,
  accountingPersonSave,
  accountingPersonDelete,
  accountingPersonCategories,
} from "@/api/accounting"
import type { Person, PersonCategory } from "@/api/accounting"
import { Users, Loader2, Plus, Pencil, Trash2 } from "lucide-react"

const PERSON_TYPES = [
  { value: "customer", label: "مشتری" },
  { value: "supplier", label: "تأمین‌کننده" },
  { value: "both", label: "هر دو" },
]

export function PersonsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<Person[]>([])
  const [total, setTotal] = useState(0)
  const [categories, setCategories] = useState<PersonCategory[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState("")
  const [page, setPage] = useState(1)
  const perPage = 15
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState<Partial<Person> & { name: string }>({
    name: "",
    code: "",
    person_type: "both",
    is_active: 1,
  })
  const [deleteId, setDeleteId] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    const params: Record<string, string | number> = { page, per_page: perPage }
    if (search.trim()) params.search = search.trim()
    accountingPersonsList(params).then((res) => {
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
    accountingPersonCategories().then((res) => {
      if (res.success && res.data?.items) setCategories(res.data.items)
    })
  }, [])

  const openCreate = () => {
    setEditingId(null)
    setForm({ name: "", code: "", person_type: "both", is_active: 1 })
    setDialogOpen(true)
  }

  const openEdit = (id: number) => {
    accountingPersonGet(id).then((res) => {
      if (res.success && res.data?.person) {
        setForm(res.data.person)
        setEditingId(id)
        setDialogOpen(true)
      }
    })
  }

  const handleSave = () => {
    if (!form.name?.trim()) return
    setSaving(true)
    accountingPersonSave({ ...form, id: editingId ?? undefined }).then((res) => {
      setSaving(false)
      if (res.success) {
        setDialogOpen(false)
        load()
      }
    })
  }

  const handleDelete = (id: number) => {
    accountingPersonDelete(id).then((res) => {
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
            <Users className="h-5 w-5" />
            اشخاص (طرف‌های حساب)
          </h2>
          <p className="text-muted-foreground text-sm">مدیریت مشتریان و تأمین‌کنندگان</p>
        </div>
        <Button onClick={openCreate}>
          <Plus className="h-4 w-4 ml-2" />
          شخص جدید
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-wrap items-center gap-4">
            <CardTitle className="text-base">لیست اشخاص</CardTitle>
            <Input
              placeholder="جستجو (نام، کد، موبایل، شناسه ملی...)"
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
            <p className="text-muted-foreground">شخصی یافت نشد.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>نام</TableHead>
                    <TableHead>کد</TableHead>
                    <TableHead>موبایل / تلفن</TableHead>
                    <TableHead>نوع</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead className="w-[100px]">عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell className="font-medium">{row.name}</TableCell>
                      <TableCell>{row.code || "—"}</TableCell>
                      <TableCell>{row.mobile || row.phone || "—"}</TableCell>
                      <TableCell>
                        {PERSON_TYPES.find((t) => t.value === row.person_type)?.label ?? row.person_type}
                      </TableCell>
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
                    {total} نفر — صفحه {page} از {Math.ceil(total / perPage)}
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
            <DialogTitle>{editingId ? "ویرایش شخص" : "شخص جدید"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>نام *</Label>
                <Input
                  value={form.name ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  placeholder="نام شخص"
                />
              </div>
              <div className="space-y-2">
                <Label>کد</Label>
                <Input
                  value={form.code ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))}
                  placeholder="کد"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>موبایل</Label>
                <Input
                  value={form.mobile ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, mobile: e.target.value }))}
                  placeholder="09..."
                />
              </div>
              <div className="space-y-2">
                <Label>تلفن</Label>
                <Input
                  value={form.phone ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                  placeholder="تلفن ثابت"
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>شناسه ملی</Label>
                <Input
                  value={form.national_id ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, national_id: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>کد اقتصادی</Label>
                <Input
                  value={form.economic_code ?? ""}
                  onChange={(e) => setForm((f) => ({ ...f, economic_code: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>آدرس</Label>
              <Input
                value={form.address ?? ""}
                onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                placeholder="آدرس"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>نوع شخص</Label>
                <select
                  className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                  value={form.person_type ?? "both"}
                  onChange={(e) => setForm((f) => ({ ...f, person_type: e.target.value as Person["person_type"] }))}
                >
                  {PERSON_TYPES.map((t) => (
                    <option key={t.value} value={t.value}>
                      {t.label}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label>دسته</Label>
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
            <Button onClick={handleSave} disabled={saving || !form.name?.trim()}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={deleteId !== null} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف شخص</AlertDialogTitle>
            <AlertDialogDescription>آیا از حذف این شخص اطمینان دارید؟</AlertDialogDescription>
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
