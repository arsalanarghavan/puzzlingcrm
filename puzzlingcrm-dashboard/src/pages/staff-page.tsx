import { useCallback, useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
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
import { SELECT_NONE_VALUE } from "@/lib/constants"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
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
  getUsers,
  manageUser,
  deleteUser,
  type CustomerUser,
  type GetUsersResponse,
} from "@/api/customers"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Pencil, Trash2 } from "lucide-react"

const STAFF_ROLES = [
  { slug: "system_manager", name: "مدیر سیستم" },
  { slug: "finance_manager", name: "مدیر مالی" },
  { slug: "team_member", name: "عضو تیم" },
  { slug: "customer", name: "مشتری" },
]

export function StaffPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [data, setData] = useState<GetUsersResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [formOpen, setFormOpen] = useState(false)
  const [editUser, setEditUser] = useState<CustomerUser | null>(null)
  const [deleteUserId, setDeleteUserId] = useState<number | null>(null)
  const [deleteNonce, setDeleteNonce] = useState<string>("")
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    email: "",
    pzl_mobile_phone: "",
    role: "team_member",
    password: "",
    department: 0,
    job_title: 0,
  })

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getUsers({ role: "staff", search: search || undefined })
      if (res.success && res.data) {
        setData(res.data)
      } else {
        setError(res.message ?? "خطا در بارگذاری کارکنان")
      }
    } catch {
      setError("خطا در بارگذاری کارکنان")
    } finally {
      setLoading(false)
    }
  }, [search])

  useEffect(() => {
    load()
  }, [load])

  const openCreate = () => {
    setEditUser(null)
    setForm({
      first_name: "",
      last_name: "",
      email: "",
      pzl_mobile_phone: "",
      role: "team_member",
      password: "",
      department: 0,
      job_title: 0,
    })
    setFormOpen(true)
  }

  const openEdit = (u: CustomerUser) => {
    setEditUser(u)
    setForm({
      first_name: u.first_name ?? "",
      last_name: u.last_name ?? "",
      email: u.email ?? "",
      pzl_mobile_phone: u.phone ?? "",
      role: u.role_slug ?? "team_member",
      password: "",
      department: 0,
      job_title: 0,
    })
    setFormOpen(true)
  }

  const handleSubmit = useCallback(async () => {
    if (!form.last_name.trim() || !form.email.trim() || !form.role) {
      setError("نام خانوادگی، ایمیل و نقش الزامی هستند.")
      return
    }
    if (!editUser && !form.password) {
      setError("برای کاربر جدید، رمز عبور الزامی است.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await manageUser({
        user_id: editUser?.id,
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        email: form.email.trim(),
        pzl_mobile_phone: form.pzl_mobile_phone.trim() || undefined,
        role: form.role,
        password: form.password || undefined,
        department: form.department || undefined,
        job_title: form.job_title || undefined,
      })
      if (res.success) {
        setFormOpen(false)
        load()
      } else {
        setError(res.message ?? "خطا در ذخیره")
      }
    } catch {
      setError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }, [form, editUser, load])

  const handleDelete = useCallback(async () => {
    if (deleteUserId == null || !deleteNonce) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await deleteUser(deleteUserId, deleteNonce)
      if (res.success) {
        setDeleteUserId(null)
        setDeleteNonce("")
        load()
      } else {
        setError(res.message ?? "خطا در حذف")
      }
    } catch {
      setError("خطا در حذف")
    } finally {
      setSubmitting(false)
    }
  }, [deleteUserId, deleteNonce, load])

  const users = data?.users ?? []
  const departments = data?.departments ?? []
  const jobTitles = data?.job_titles ?? []
  const jobTitlesForDept = form.department
    ? jobTitles.filter((j) => j.parent === form.department)
    : []

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-xl font-semibold">کارکنان</h2>
        <Button onClick={openCreate} className="gap-2">
          <Plus className="h-4 w-4" />
          افزودن کارمند
        </Button>
      </div>

      {error && (
        <div className="rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3 text-destructive text-sm">
          {error}
        </div>
      )}

      <Card>
        <CardContent className="pt-6">
          <div className="mb-4">
            <Input
              placeholder="جستجو (نام، ایمیل، تلفن)..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="max-w-sm"
            />
          </div>
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : users.length === 0 ? (
            <div className="py-12 text-center text-muted-foreground">هیچ کارمندی یافت نشد.</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>نام</TableHead>
                  <TableHead>ایمیل</TableHead>
                  <TableHead>دپارتمان</TableHead>
                  <TableHead>عنوان شغلی</TableHead>
                  <TableHead>نقش</TableHead>
                  <TableHead className="w-[120px]">عملیات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {users.map((u) => (
                  <TableRow key={u.id}>
                    <TableCell className="font-medium">
                      {(u.first_name || u.last_name)?.trim()
                        ? `${u.first_name ?? ""} ${u.last_name ?? ""}`.trim()
                        : u.display_name}
                    </TableCell>
                    <TableCell>{u.email}</TableCell>
                    <TableCell>{(u as CustomerUser & { department_name?: string }).department_name ?? "—"}</TableCell>
                    <TableCell>{(u as CustomerUser & { job_title_name?: string }).job_title_name ?? "—"}</TableCell>
                    <TableCell>{u.role_name}</TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(u)}>
                          <Pencil className="h-4 w-4" />
                        </Button>
                        {u.can_delete && (
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 text-destructive hover:text-destructive"
                            onClick={() => {
                              setDeleteUserId(u.id)
                              setDeleteNonce(u.delete_nonce)
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={formOpen} onOpenChange={setFormOpen}>
        <DialogContent className="sm:max-w-md" dir={isRtl ? "rtl" : "ltr"}>
          <DialogHeader>
            <DialogTitle>{editUser ? "ویرایش کارمند" : "افزودن کارمند"}</DialogTitle>
          </DialogHeader>
          <form
            onSubmit={(e) => {
              e.preventDefault()
              handleSubmit()
            }}
            className="space-y-4 py-4"
          >
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">نام</label>
                <Input
                  value={form.first_name}
                  onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))}
                  placeholder="نام"
                />
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">نام خانوادگی *</label>
                <Input
                  value={form.last_name}
                  onChange={(e) => setForm((f) => ({ ...f, last_name: e.target.value }))}
                  placeholder="نام خانوادگی"
                />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">ایمیل *</label>
              <Input
                type="email"
                value={form.email}
                onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                placeholder="email@example.com"
                disabled={!!editUser}
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">موبایل</label>
              <Input
                value={form.pzl_mobile_phone}
                onChange={(e) => setForm((f) => ({ ...f, pzl_mobile_phone: e.target.value }))}
                placeholder="09..."
              />
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">نقش *</label>
              <Select value={form.role} onValueChange={(v) => setForm((f) => ({ ...f, role: v }))}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {STAFF_ROLES.map((r) => (
                    <SelectItem key={r.slug} value={r.slug}>{r.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">رمز عبور {editUser ? "(خالی = بدون تغییر)" : "*"}</label>
              <Input
                type="password"
                value={form.password}
                onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                placeholder={editUser ? "خالی بگذارید" : "رمز عبور"}
              />
            </div>
            {departments.length > 0 && (
              <div className="space-y-2">
                <label className="text-sm font-medium">دپارتمان</label>
                <Select
                  value={form.department ? String(form.department) : SELECT_NONE_VALUE}
                  onValueChange={(v) =>
                    setForm((f) => ({
                      ...f,
                      department: v === SELECT_NONE_VALUE ? 0 : parseInt(v, 10) || 0,
                      job_title: 0,
                    }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="انتخاب دپارتمان" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_NONE_VALUE}>—</SelectItem>
                    {departments.map((d) => (
                      <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
            {jobTitlesForDept.length > 0 && (
              <div className="space-y-2">
                <label className="text-sm font-medium">عنوان شغلی</label>
                <Select
                  value={form.job_title ? String(form.job_title) : SELECT_NONE_VALUE}
                  onValueChange={(v) =>
                    setForm((f) => ({
                      ...f,
                      job_title: v === SELECT_NONE_VALUE ? 0 : parseInt(v, 10) || 0,
                    }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="انتخاب عنوان شغلی" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SELECT_NONE_VALUE}>—</SelectItem>
                    {jobTitlesForDept.map((j) => (
                      <SelectItem key={j.id} value={String(j.id)}>{j.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>انصراف</Button>
            <Button type="submit" disabled={submitting}>
              {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
              {editUser ? "ذخیره" : "ایجاد"}
            </Button>
          </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <AlertDialog
        open={deleteUserId !== null}
        onOpenChange={(open: boolean) => !open && setDeleteUserId(null)}
      >
        <AlertDialogContent dir={isRtl ? "rtl" : "ltr"}>
          <AlertDialogHeader>
            <AlertDialogTitle>حذف کارمند</AlertDialogTitle>
            <AlertDialogDescription>
              آیا از حذف این کاربر اطمینان دارید؟
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>انصراف</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={handleDelete}
              disabled={submitting}
            >
              {submitting ? <Loader2 className="h-4 w-4 animate-spin" /> : "حذف"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
