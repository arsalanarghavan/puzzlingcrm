import { useCallback, useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
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
import { SELECT_ALL_VALUE } from "@/lib/constants"
import { getConfigOrNull } from "@/api/client"
import {
  getUsers,
  manageUser,
  deleteUser,
  sendCustomSms,
  type CustomerUser,
} from "@/api/customers"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Pencil, Trash2, MessageSquare, Users, UserCircle, UserCheck } from "lucide-react"

export function CustomersPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [users, setUsers] = useState<CustomerUser[]>([])
  const [stats, setStats] = useState({ total: 0, customers: 0, staff: 0 })
  const [roles, setRoles] = useState<{ slug: string; name: string }[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [roleFilter, setRoleFilter] = useState("")
  const [dialogOpen, setDialogOpen] = useState(false)
  const [smsDialogOpen, setSmsDialogOpen] = useState(false)
  const [editingUser, setEditingUser] = useState<CustomerUser | null>(null)
  const [smsUser, setSmsUser] = useState<CustomerUser | null>(null)
  const [smsMessage, setSmsMessage] = useState("")
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    email: "",
    pzl_mobile_phone: "",
    role: "customer",
    password: "",
  })

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getUsers({ search: search || undefined, role: roleFilter || undefined })
      if (res.success && res.data) {
        const d = res.data as { users: CustomerUser[]; stats: { total: number; customers: number; staff: number }; roles: { slug: string; name: string }[] }
        setUsers(d.users ?? [])
        setStats(d.stats ?? { total: 0, customers: 0, staff: 0 })
        setRoles(d.roles ?? [])
      } else {
        setError(res.message ?? "خطا در بارگذاری کاربران")
      }
    } catch {
      setError("خطا در بارگذاری کاربران")
    } finally {
      setLoading(false)
    }
  }, [search, roleFilter])

  useEffect(() => {
    const t = setTimeout(load, search || roleFilter ? 300 : 0)
    return () => clearTimeout(t)
  }, [load, search, roleFilter])

  const openNew = () => {
    setEditingUser(null)
    setForm({
      first_name: "",
      last_name: "",
      email: "",
      pzl_mobile_phone: "",
      role: "customer",
      password: "",
    })
    setDialogOpen(true)
  }

  const openEdit = (u: CustomerUser) => {
    setEditingUser(u)
    setForm({
      first_name: u.first_name ?? "",
      last_name: u.last_name ?? "",
      email: u.email ?? "",
      pzl_mobile_phone: u.phone ?? "",
      role: u.role_slug || "customer",
      password: "",
    })
    setDialogOpen(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.last_name.trim() || !form.email.trim() || !form.role) {
      setError("نام خانوادگی، ایمیل و نقش الزامی هستند.")
      return
    }
    if (!editingUser && !form.password.trim()) {
      setError("برای کاربر جدید، رمز عبور الزامی است.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const res = await manageUser({
        user_id: editingUser?.id,
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        email: form.email.trim(),
        pzl_mobile_phone: form.pzl_mobile_phone.trim() || undefined,
        role: form.role,
        password: form.password.trim() || undefined,
      })
      if (res.success) {
        setDialogOpen(false)
        load()
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

  const handleDelete = async (u: CustomerUser) => {
    if (!u.can_delete || !u.delete_nonce) return
    if (!confirm("آیا از حذف این کاربر اطمینان دارید؟")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await deleteUser(u.id, u.delete_nonce)
      if (res.success) load()
      else setError(res.message ?? "خطا در حذف")
    } catch {
      setError("خطا در حذف")
    } finally {
      setSubmitting(false)
    }
  }

  const openSms = (u: CustomerUser) => {
    setSmsUser(u)
    setSmsMessage("")
    setSmsDialogOpen(true)
  }

  const handleSendSms = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!smsUser || !smsMessage.trim()) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await sendCustomSms(smsUser.id, smsMessage.trim())
      if (res.success) {
        setSmsDialogOpen(false)
        setSmsUser(null)
        setSmsMessage("")
      } else {
        setError(res.message ?? "خطا در ارسال پیامک")
      }
    } catch {
      setError("خطا در ارسال پیامک")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">مدیریت مشتریان و کاربران</h1>
        <Button onClick={openNew}>
          <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
          افزودن کاربر جدید
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="rounded-lg bg-primary/10 p-3">
                <Users className="h-6 w-6 text-primary" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">کل کاربران</p>
                <p className="text-2xl font-semibold">{stats.total}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="rounded-lg bg-success/10 p-3">
                <UserCircle className="h-6 w-6 text-success" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">مشتریان</p>
                <p className="text-2xl font-semibold">{stats.customers}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="rounded-lg bg-amber-500/10 p-3">
                <UserCheck className="h-6 w-6 text-amber-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">کارمندان</p>
                <p className="text-2xl font-semibold">{stats.staff}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-wrap gap-4">
            <div className="flex-1 min-w-[200px]">
              <Label>جستجوی سریع</Label>
              <div className="mt-1 flex rounded-md border">
                <Input
                  dir="ltr"
                  placeholder="نام، ایمیل، موبایل..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="border-0 focus-visible:ring-0"
                />
              </div>
            </div>
            <div className="w-[180px]">
              <Label>نقش</Label>
              <Select
                value={roleFilter === "" ? SELECT_ALL_VALUE : roleFilter}
                onValueChange={(v) => setRoleFilter(v === SELECT_ALL_VALUE ? "" : v)}
              >
                <SelectTrigger className="mt-1">
                  <SelectValue placeholder="همه نقش‌ها" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={SELECT_ALL_VALUE}>همه نقش‌ها</SelectItem>
                  {roles.map((r) => (
                    <SelectItem key={r.slug} value={r.slug}>
                      {r.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : users.length === 0 ? (
            <div className="py-12 text-center text-muted-foreground">
              هیچ کاربری یافت نشد.
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>کاربر</TableHead>
                  <TableHead>ایمیل</TableHead>
                  <TableHead>موبایل</TableHead>
                  <TableHead>نقش</TableHead>
                  <TableHead>تاریخ عضویت</TableHead>
                  <TableHead>عملیات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {users.map((u) => (
                  <TableRow key={u.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <Avatar className="h-10 w-10">
                          <AvatarImage src={u.avatar_url} alt="" />
                          <AvatarFallback>{u.display_name?.slice(0, 2) ?? "?"}</AvatarFallback>
                        </Avatar>
                        <div>
                          <div className="font-medium">{u.display_name}</div>
                          <div className="text-xs text-muted-foreground">ID: {u.id}</div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell dir="ltr" className="font-mono text-sm">
                      {u.email}
                    </TableCell>
                    <TableCell dir="ltr">{u.phone || "—"}</TableCell>
                    <TableCell>
                      <Badge variant="secondary">{u.role_name}</Badge>
                    </TableCell>
                    <TableCell>{u.registered_jalali}</TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={() => openEdit(u)}>
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => openSms(u)}
                          title="ارسال پیامک"
                        >
                          <MessageSquare className="h-4 w-4" />
                        </Button>
                        {u.can_delete && (
                          <Button
                            variant="outline"
                            size="sm"
                            className="text-destructive hover:text-destructive"
                            onClick={() => handleDelete(u)}
                            disabled={submitting}
                            title="حذف"
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

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {editingUser ? "ویرایش اطلاعات کاربر" : "افزودن کاربر جدید"}
            </DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="first_name">نام</Label>
                <Input
                  id="first_name"
                  value={form.first_name}
                  onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))}
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
              <div className="space-y-2">
                <Label htmlFor="email">ایمیل *</Label>
                <Input
                  id="email"
                  type="email"
                  dir="ltr"
                  value={form.email}
                  onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">شماره موبایل</Label>
                <Input
                  id="phone"
                  dir="ltr"
                  value={form.pzl_mobile_phone}
                  onChange={(e) => setForm((f) => ({ ...f, pzl_mobile_phone: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="role">نقش کاربری *</Label>
                <Select
                  value={form.role}
                  onValueChange={(v) => setForm((f) => ({ ...f, role: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {roles.map((r) => (
                      <SelectItem key={r.slug} value={r.slug}>
                        {r.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">
                  رمز عبور {editingUser ? "(برای عدم تغییر خالی بگذارید)" : "*"}
                </Label>
                <Input
                  id="password"
                  type="password"
                  dir="ltr"
                  value={form.password}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                  required={!editingUser}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                انصراف
              </Button>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                {editingUser ? "ذخیره تغییرات" : "ایجاد کاربر"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={smsDialogOpen} onOpenChange={setSmsDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>ارسال پیامک به {smsUser?.display_name}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSendSms} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="sms_message">متن پیام</Label>
              <textarea
                id="sms_message"
                className="flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                value={smsMessage}
                onChange={(e) => setSmsMessage(e.target.value)}
                required
                rows={5}
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setSmsDialogOpen(false)}>
                انصراف
              </Button>
              <Button type="submit" disabled={submitting || !smsMessage.trim()}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                ارسال پیامک
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
