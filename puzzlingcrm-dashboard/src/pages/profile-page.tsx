import { useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { cn } from "@/lib/utils"
import { getConfigOrNull } from "@/api/client"
import { updateProfile } from "@/api/profile"
import { Loader2, User } from "lucide-react"

export function ProfilePage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const user = config?.user

  const [form, setForm] = useState({
    first_name: user?.first_name ?? user?.name?.split(" ")?.[0] ?? "",
    last_name: user?.last_name ?? user?.name?.split(" ")?.slice(1).join(" ") ?? "",
    pzl_mobile_phone: user?.pzl_mobile_phone ?? "",
    password: "",
    password_confirm: "",
  })
  const [avatarFile, setAvatarFile] = useState<File | null>(null)
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState(false)

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setAvatarFile(file)
      setAvatarPreview(URL.createObjectURL(file))
    } else {
      setAvatarFile(null)
      setAvatarPreview(null)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setSuccess(false)
    if (!form.last_name.trim()) {
      setError("نام خانوادگی ضروری است.")
      return
    }
    if (form.password && form.password !== form.password_confirm) {
      setError("رمزهای عبور وارد شده یکسان نیستند.")
      return
    }
    setSubmitting(true)
    try {
      const res = await updateProfile({
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        pzl_mobile_phone: form.pzl_mobile_phone.trim() || undefined,
        password: form.password || undefined,
        password_confirm: form.password_confirm || undefined,
        pzl_profile_picture: avatarFile ?? undefined,
      })
      if (res.success) {
        setSuccess(true)
        setForm((f) => ({ ...f, password: "", password_confirm: "" }))
        setAvatarFile(null)
        setAvatarPreview(null)
        if ((res.data as { reload?: boolean })?.reload) {
          window.location.reload()
        }
      } else {
        setError(res.message ?? "خطا در به‌روزرسانی پروفایل")
      }
    } catch {
      setError("خطا در به‌روزرسانی پروفایل")
    } finally {
      setSubmitting(false)
    }
  }

  const avatarUrl = avatarPreview ?? user?.avatarLarge ?? user?.avatar

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">پروفایل من</h1>
      </div>
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      {success && (
        <Alert>
          <AlertDescription>پروفایل شما با موفقیت به‌روزرسانی شد.</AlertDescription>
        </Alert>
      )}
      <Card>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="flex flex-col gap-6 md:flex-row">
              <div className="flex flex-col items-start gap-2">
                <Label>عکس پروفایل</Label>
                <div className="flex items-center gap-4">
                  <Avatar className="h-24 w-24">
                    <AvatarImage src={avatarUrl} alt={user?.name} />
                    <AvatarFallback>
                      <User className="h-12 w-12" />
                    </AvatarFallback>
                  </Avatar>
                  <Input
                    type="file"
                    accept="image/*"
                    onChange={handleFileChange}
                    className="max-w-[200px]"
                  />
                </div>
              </div>
              <div className="flex-1 space-y-4">
                <h4 className="font-medium">اطلاعات اصلی و ورود</h4>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="first_name">نام</Label>
                    <Input
                      id="first_name"
                      value={form.first_name}
                      onChange={(e) =>
                        setForm((f) => ({ ...f, first_name: e.target.value }))
                      }
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="last_name">نام خانوادگی *</Label>
                    <Input
                      id="last_name"
                      value={form.last_name}
                      onChange={(e) =>
                        setForm((f) => ({ ...f, last_name: e.target.value }))
                      }
                      required
                    />
                  </div>
                  <div className="space-y-2 sm:col-span-2">
                    <Label htmlFor="email">ایمیل (غیرقابل تغییر)</Label>
                    <Input
                      id="email"
                      type="email"
                      value={user?.email ?? ""}
                      disabled
                      className="bg-muted"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="pzl_mobile_phone">شماره موبایل</Label>
                    <Input
                      id="pzl_mobile_phone"
                      dir="ltr"
                      className="text-left"
                      value={form.pzl_mobile_phone}
                      onChange={(e) =>
                        setForm((f) => ({
                          ...f,
                          pzl_mobile_phone: e.target.value,
                        }))
                      }
                    />
                  </div>
                </div>
                <hr />
                <h4 className="font-medium">تغییر رمز عبور</h4>
                <p className="text-sm text-muted-foreground">
                  برای تغییر رمز عبور، هر دو فیلد زیر را پر کنید. در غیر این صورت، آن را
                  خالی بگذارید.
                </p>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="password">رمز عبور جدید</Label>
                    <Input
                      id="password"
                      type="password"
                      value={form.password}
                      onChange={(e) =>
                        setForm((f) => ({ ...f, password: e.target.value }))
                      }
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="password_confirm">تکرار رمز عبور جدید</Label>
                    <Input
                      id="password_confirm"
                      type="password"
                      value={form.password_confirm}
                      onChange={(e) =>
                        setForm((f) => ({
                          ...f,
                          password_confirm: e.target.value,
                        }))
                      }
                    />
                  </div>
                </div>
              </div>
            </div>
            <div>
              <Button type="submit" disabled={submitting}>
                {submitting && <Loader2 className={cn("h-4 w-4 animate-spin", isRtl ? "ms-2" : "me-2")} />}
                ذخیره تغییرات
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
