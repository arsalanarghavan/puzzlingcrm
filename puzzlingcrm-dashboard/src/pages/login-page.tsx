import { useState, useCallback, useEffect } from "react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  sendLoginOtp,
  verifyLoginOtp,
  sendEmailOtp,
  verifyEmailOtp,
  loginWithPassword,
  setPassword as setPasswordApi,
  getLoginConfig,
} from "@/api/login";
import { Loader2, Smartphone, Mail, User, Eye, EyeOff } from "lucide-react";

const PHONE_PATTERN = /^(09|۰۹)[0-9۰-۹]{9}$/;
function normalizeDigits(s: string): string {
  return s.replace(/[۰-۹]/g, (c) => String("۰۱۲۳۴۵۶۷۸۹".indexOf(c)));
}

function useTimer(initialSeconds: number, active: boolean, onExpire?: () => void) {
  const [seconds, setSeconds] = useState(initialSeconds);
  useEffect(() => {
    if (!active || seconds <= 0) return;
    const id = setInterval(() => {
      setSeconds((prev) => {
        if (prev <= 1) {
          onExpire?.();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(id);
  }, [active, seconds, onExpire]);
  const start = useCallback((sec: number) => setSeconds(sec), []);
  return { seconds, start, formatted: `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, "0")}` };
}

export function LoginPage() {
  const config = getLoginConfig();
  const [message, setMessage] = useState<{ type: "error" | "success"; text: string } | null>(null);
  const [loading, setLoading] = useState(false);

  // Phone flow
  const [phone, setPhone] = useState("");
  const [phoneStep, setPhoneStep] = useState<"input" | "otp" | "password">("input");
  const [otpCode, setOtpCode] = useState("");
  const [phoneOtpSent, setPhoneOtpSent] = useState(false);
  const phoneTimer = useTimer(300, phoneOtpSent && phoneStep === "otp", () => setPhoneOtpSent(false));
  const [showPhonePassword, setShowPhonePassword] = useState(false);
  const [phonePassword, setPhonePassword] = useState("");
  const [remember, setRemember] = useState(false);

  // Set password (after phone OTP for new users)
  const [setPasswordStep, setSetPasswordStep] = useState(false);
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showNewPass, setShowNewPass] = useState(false);
  const [showConfirmPass, setShowConfirmPass] = useState(false);

  // Email flow
  const [email, setEmail] = useState("");
  const [emailStep, setEmailStep] = useState<"input" | "otp">("input");
  const [emailOtpCode, setEmailOtpCode] = useState("");
  const [emailOtpSent, setEmailOtpSent] = useState(false);
  const [showEmailPassword, setShowEmailPassword] = useState(false);
  const [emailPassword, setEmailPassword] = useState("");
  const emailTimer = useTimer(300, emailOtpSent && emailStep === "otp", () => setEmailOtpSent(false));

  // Username flow
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);

  const handleSendPhoneOtp = async () => {
    const normalized = normalizeDigits(phone).replace(/\D/g, "");
    if (!PHONE_PATTERN.test(normalized) && !/^09[0-9]{9}$/.test(normalized)) {
      setMessage({ type: "error", text: "فرمت شماره موبایل صحیح نیست (مثال: 09123456789)" });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      await sendLoginOtp(normalized);
      setPhone(normalized);
      setPhoneStep("otp");
      setPhoneOtpSent(true);
      phoneTimer.start(300);
      setMessage({ type: "success", text: "کد تایید به شماره شما ارسال شد." });
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "خطا در ارسال کد" });
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyPhoneOtp = async () => {
    const code = normalizeDigits(otpCode).replace(/\D/g, "");
    if (code.length < 4) {
      setMessage({ type: "error", text: "کد تایید را وارد کنید." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      const data = await verifyLoginOtp(phone, code);
      if (data.needs_password) {
        setSetPasswordStep(true);
        setPhoneStep("password");
      } else if (data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "کد تایید اشتباه است" });
    } finally {
      setLoading(false);
    }
  };

  const handleSetPassword = async () => {
    if (newPassword.length < 6) {
      setMessage({ type: "error", text: "رمز عبور باید حداقل 6 کاراکتر باشد." });
      return;
    }
    if (newPassword !== confirmPassword) {
      setMessage({ type: "error", text: "رمز عبور و تکرار آن یکسان نیست." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      const data = await setPasswordApi(phone, newPassword, confirmPassword);
      if (data.redirect_url) window.location.href = data.redirect_url;
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "خطا در تنظیم رمز" });
    } finally {
      setLoading(false);
    }
  };

  const handlePhonePasswordLogin = async () => {
    if (!phonePassword) {
      setMessage({ type: "error", text: "رمز عبور را وارد کنید." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      const data = await loginWithPassword(phone, phonePassword, remember);
      if (data.redirect_url) window.location.href = data.redirect_url;
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "ورود ناموفق" });
    } finally {
      setLoading(false);
    }
  };

  const handleSendEmailOtp = async () => {
    const e = email.trim();
    if (!e || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)) {
      setMessage({ type: "error", text: "یک ایمیل معتبر وارد کنید." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      await sendEmailOtp(e);
      setEmail(e);
      setEmailStep("otp");
      setEmailOtpSent(true);
      emailTimer.start(300);
      setMessage({ type: "success", text: "کد تایید به ایمیل شما ارسال شد." });
    } catch (err) {
      setMessage({ type: "error", text: err instanceof Error ? err.message : "خطا در ارسال کد" });
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyEmailOtp = async () => {
    const code = normalizeDigits(emailOtpCode).replace(/\D/g, "");
    if (code.length < 4) {
      setMessage({ type: "error", text: "کد تایید را وارد کنید." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      const data = await verifyEmailOtp(email, code);
      if (data.redirect_url) window.location.href = data.redirect_url;
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "کد تایید اشتباه است" });
    } finally {
      setLoading(false);
    }
  };

  const handleEmailPasswordLogin = async () => {
    if (!emailPassword) {
      setMessage({ type: "error", text: "رمز عبور را وارد کنید." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      const data = await loginWithPassword(email, emailPassword, remember);
      if (data.redirect_url) window.location.href = data.redirect_url;
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "ورود ناموفق" });
    } finally {
      setLoading(false);
    }
  };

  const handleUsernameLogin = async () => {
    if (!username.trim() || !password) {
      setMessage({ type: "error", text: "نام کاربری و رمز عبور را وارد کنید." });
      return;
    }
    setLoading(true);
    setMessage(null);
    try {
      const data = await loginWithPassword(username.trim(), password, remember);
      if (data.redirect_url) window.location.href = data.redirect_url;
    } catch (e) {
      setMessage({ type: "error", text: e instanceof Error ? e.message : "ورود ناموفق" });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4" dir={config?.dir || "rtl"}>
      <Card className="w-full max-w-md shadow-lg">
        <CardHeader className="text-center space-y-1">
          {config?.logoUrl && (
            <div className="flex justify-center mb-2">
              <img src={config.logoUrl} alt="" className="h-12 object-contain" />
            </div>
          )}
          <CardTitle className="text-xl">{config?.siteName || "ورود"}</CardTitle>
          <CardDescription>ورود یا ثبت‌نام به پنل مدیریت</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {message && (
            <Alert variant={message.type === "error" ? "destructive" : "success"} className="py-2">
              <AlertDescription>{message.text}</AlertDescription>
            </Alert>
          )}

          <Tabs defaultValue="phone" className="w-full">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="phone" className="gap-1">
                <Smartphone className="h-4 w-4" />
                موبایل
              </TabsTrigger>
              <TabsTrigger value="email" className="gap-1">
                <Mail className="h-4 w-4" />
                ایمیل
              </TabsTrigger>
              <TabsTrigger value="username" className="gap-1">
                <User className="h-4 w-4" />
                نام کاربری
              </TabsTrigger>
            </TabsList>

            {/* Phone tab */}
            <TabsContent value="phone" className="space-y-4 mt-4">
              {phoneStep === "input" && !showPhonePassword && (
                <>
                  <div className="space-y-2">
                    <Label htmlFor="phone">شماره موبایل</Label>
                    <Input
                      id="phone"
                      type="tel"
                      placeholder="09123456789"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 11))}
                      className="font-mono"
                    />
                  </div>
                  <div className="flex flex-col gap-2">
                    <Button onClick={handleSendPhoneOtp} disabled={loading} className="w-full">
                      {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                      دریافت کد پیامک
                    </Button>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => setShowPhonePassword(true)}
                    >
                      ورود با رمز عبور
                    </Button>
                  </div>
                </>
              )}
              {phoneStep === "input" && showPhonePassword && (
                <>
                  <div className="space-y-2">
                    <Label htmlFor="phone-pw">شماره موبایل</Label>
                    <Input
                      id="phone-pw"
                      type="tel"
                      placeholder="09123456789"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 11))}
                      className="font-mono"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="phone-password">رمز عبور</Label>
                    <div className="relative">
                      <Input
                        id="phone-password"
                        type={showPassword ? "text" : "password"}
                        placeholder="رمز عبور"
                        value={phonePassword}
                        onChange={(e) => setPhonePassword(e.target.value)}
                        className="pr-9"
                      />
                      <button
                        type="button"
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                        onClick={() => setShowPassword(!showPassword)}
                      >
                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </button>
                    </div>
                  </div>
                  <label className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={remember}
                      onChange={(e) => setRemember(e.target.checked)}
                    />
                    مرا به خاطر بسپار
                  </label>
                  <div className="flex gap-2">
                    <Button onClick={handlePhonePasswordLogin} disabled={loading} className="flex-1">
                      {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                      ورود
                    </Button>
                    <Button variant="outline" onClick={() => setShowPhonePassword(false)}>
                      بازگشت
                    </Button>
                  </div>
                </>
              )}
              {phoneStep === "otp" && !setPasswordStep && (
                <>
                  <Alert variant="default" className="text-sm">
                    کد به شماره {phone} ارسال شد.
                  </Alert>
                  <div className="space-y-2">
                    <Label htmlFor="otp">کد تایید</Label>
                    <Input
                      id="otp"
                      type="text"
                      inputMode="numeric"
                      placeholder="------"
                      maxLength={8}
                      value={otpCode}
                      onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ""))}
                      className="text-center text-lg font-mono tracking-widest"
                    />
                    {phoneOtpSent && (
                      <p className="text-xs text-muted-foreground">
                        زمان باقیمانده: {phoneTimer.formatted}
                      </p>
                    )}
                  </div>
                  <div className="flex flex-col gap-2">
                    <Button onClick={handleVerifyPhoneOtp} disabled={loading} className="w-full">
                      {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                      تایید و ورود
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={!phoneOtpSent || loading}
                      onClick={handleSendPhoneOtp}
                    >
                      ارسال مجدد کد
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => {
                        setPhoneStep("input");
                        setOtpCode("");
                        setPhoneOtpSent(false);
                      }}
                    >
                      تغییر شماره موبایل
                    </Button>
                  </div>
                </>
              )}
              {setPasswordStep && (
                <>
                  <Alert variant="success" className="text-sm">
                    لطفاً رمز عبور خود را تنظیم کنید.
                  </Alert>
                  <div className="space-y-2">
                    <Label htmlFor="new-password">رمز عبور جدید</Label>
                    <div className="relative">
                      <Input
                        id="new-password"
                        type={showNewPass ? "text" : "password"}
                        value={newPassword}
                        onChange={(e) => setNewPassword(e.target.value)}
                        className="pr-9"
                      />
                      <button
                        type="button"
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                        onClick={() => setShowNewPass(!showNewPass)}
                      >
                        {showNewPass ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </button>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="confirm-password">تکرار رمز عبور</Label>
                    <div className="relative">
                      <Input
                        id="confirm-password"
                        type={showConfirmPass ? "text" : "password"}
                        value={confirmPassword}
                        onChange={(e) => setConfirmPassword(e.target.value)}
                        className="pr-9"
                      />
                      <button
                        type="button"
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                        onClick={() => setShowConfirmPass(!showConfirmPass)}
                      >
                        {showConfirmPass ? (
                          <EyeOff className="h-4 w-4" />
                        ) : (
                          <Eye className="h-4 w-4" />
                        )}
                      </button>
                    </div>
                  </div>
                  <Button onClick={handleSetPassword} disabled={loading} className="w-full">
                    {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                    تنظیم رمز و ورود
                  </Button>
                </>
              )}
            </TabsContent>

            {/* Email tab */}
            <TabsContent value="email" className="space-y-4 mt-4">
              {emailStep === "input" && !showEmailPassword && (
                <>
                  <div className="space-y-2">
                    <Label htmlFor="email">ایمیل</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="example@domain.com"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                    />
                  </div>
                  <div className="flex flex-col gap-2">
                    <Button onClick={handleSendEmailOtp} disabled={loading} className="w-full">
                      {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                      ارسال کد به ایمیل
                    </Button>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => setShowEmailPassword(true)}
                    >
                      ورود با رمز عبور
                    </Button>
                  </div>
                </>
              )}
              {emailStep === "input" && showEmailPassword && (
                <>
                  <div className="space-y-2">
                    <Label htmlFor="email-pw">ایمیل</Label>
                    <Input
                      id="email-pw"
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="email-password">رمز عبور</Label>
                    <div className="relative">
                      <Input
                        id="email-password"
                        type={showPassword ? "text" : "password"}
                        value={emailPassword}
                        onChange={(e) => setEmailPassword(e.target.value)}
                        className="pr-9"
                      />
                      <button
                        type="button"
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                        onClick={() => setShowPassword(!showPassword)}
                      >
                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                      </button>
                    </div>
                  </div>
                  <label className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={remember}
                      onChange={(e) => setRemember(e.target.checked)}
                    />
                    مرا به خاطر بسپار
                  </label>
                  <div className="flex gap-2">
                    <Button onClick={handleEmailPasswordLogin} disabled={loading} className="flex-1">
                      {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                      ورود
                    </Button>
                    <Button variant="outline" onClick={() => setShowEmailPassword(false)}>
                      بازگشت
                    </Button>
                  </div>
                </>
              )}
              {emailStep === "otp" && (
                <>
                  <Alert variant="default" className="text-sm">
                    کد به {email} ارسال شد.
                  </Alert>
                  <div className="space-y-2">
                    <Label htmlFor="email-otp">کد تایید</Label>
                    <Input
                      id="email-otp"
                      type="text"
                      inputMode="numeric"
                      placeholder="------"
                      maxLength={8}
                      value={emailOtpCode}
                      onChange={(e) =>
                        setEmailOtpCode(e.target.value.replace(/\D/g, ""))
                      }
                      className="text-center text-lg font-mono tracking-widest"
                    />
                    {emailOtpSent && (
                      <p className="text-xs text-muted-foreground">
                        زمان باقیمانده: {emailTimer.formatted}
                      </p>
                    )}
                  </div>
                  <div className="flex flex-col gap-2">
                    <Button onClick={handleVerifyEmailOtp} disabled={loading} className="w-full">
                      {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                      تایید و ورود
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={!emailOtpSent || loading}
                      onClick={handleSendEmailOtp}
                    >
                      ارسال مجدد کد
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => {
                        setEmailStep("input");
                        setEmailOtpCode("");
                        setEmailOtpSent(false);
                      }}
                    >
                      تغییر ایمیل
                    </Button>
                  </div>
                </>
              )}
            </TabsContent>

            {/* Username tab */}
            <TabsContent value="username" className="space-y-4 mt-4">
              <div className="space-y-2">
                <Label htmlFor="username">نام کاربری</Label>
                <Input
                  id="username"
                  type="text"
                  placeholder="نام کاربری"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  autoComplete="username"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="user-password">رمز عبور</Label>
                <div className="relative">
                  <Input
                    id="user-password"
                    type={showPassword ? "text" : "password"}
                    placeholder="رمز عبور"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    autoComplete="current-password"
                    className="pr-9"
                  />
                  <button
                    type="button"
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                />
                مرا به خاطر بسپار
              </label>
              <Button onClick={handleUsernameLogin} disabled={loading} className="w-full">
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                ورود
              </Button>
              {config?.lostPasswordUrl && (
                <a
                  href={config.lostPasswordUrl}
                  className="text-sm text-primary hover:underline block text-center"
                >
                  فراموشی رمز عبور
                </a>
              )}
            </TabsContent>
          </Tabs>

          {config?.registerUrl && (
            <p className="text-center text-sm text-muted-foreground">
              حساب کاربری ندارید؟{" "}
              <a href={config.registerUrl} className="text-primary hover:underline">
                ثبت‌نام کنید
              </a>
            </p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
