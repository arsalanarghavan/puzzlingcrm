/**
 * Login page API – AJAX calls for OTP (SMS/email), password login, set password.
 * Uses window.PuzzlingLoginConfig (ajaxUrl, nonce) from PHP shell.
 */

declare global {
  interface Window {
    PuzzlingLoginConfig?: {
      ajaxUrl: string;
      nonce: string;
      logoUrl?: string;
      siteName?: string;
      dir?: string;
      lang?: string;
      lostPasswordUrl?: string;
      registerUrl?: string;
    };
  }
}

function getConfig() {
  const c = window.PuzzlingLoginConfig;
  if (!c?.ajaxUrl || !c.nonce) {
    throw new Error("PuzzlingLoginConfig not found");
  }
  return c;
}

function formData(params: Record<string, string | number | boolean | undefined>) {
  const fd = new FormData();
  fd.append("security", getConfig().nonce);
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== "") fd.append(k, String(v));
  });
  return fd;
}

export async function sendLoginOtp(phoneNumber: string) {
  const { ajaxUrl } = getConfig();
  const res = await fetch(ajaxUrl, {
    method: "POST",
    body: formData({ action: "puzzling_send_login_otp", phone_number: phoneNumber }),
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.data?.message || "خطا در ارسال کد");
  return json.data as { message: string; expires_in: number };
}

export async function verifyLoginOtp(phoneNumber: string, otpCode: string) {
  const { ajaxUrl } = getConfig();
  const res = await fetch(ajaxUrl, {
    method: "POST",
    body: formData({
      action: "puzzling_verify_login_otp",
      phone_number: phoneNumber,
      otp_code: otpCode,
    }),
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.data?.message || "کد تایید اشتباه است");
  return json.data as { message: string; redirect_url: string; needs_password?: boolean };
}

export async function sendEmailOtp(email: string) {
  const { ajaxUrl } = getConfig();
  const res = await fetch(ajaxUrl, {
    method: "POST",
    body: formData({ action: "puzzling_send_email_otp", email }),
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.data?.message || "خطا در ارسال کد");
  return json.data as { message: string; expires_in: number };
}

export async function verifyEmailOtp(email: string, otpCode: string) {
  const { ajaxUrl } = getConfig();
  const res = await fetch(ajaxUrl, {
    method: "POST",
    body: formData({
      action: "puzzling_verify_email_otp",
      email,
      otp_code: otpCode,
    }),
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.data?.message || "کد تایید اشتباه است");
  return json.data as { message: string; redirect_url: string };
}

export async function loginWithPassword(
  username: string,
  password: string,
  remember: boolean
) {
  const { ajaxUrl } = getConfig();
  const res = await fetch(ajaxUrl, {
    method: "POST",
    body: formData({
      action: "puzzling_login_with_password",
      username,
      password,
      remember: remember ? 1 : 0,
    }),
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.data?.message || "ورود ناموفق");
  return json.data as { message: string; redirect_url: string };
}

export async function setPassword(
  phoneNumber: string,
  password: string,
  confirmPassword: string
) {
  const { ajaxUrl } = getConfig();
  const res = await fetch(ajaxUrl, {
    method: "POST",
    body: formData({
      action: "puzzling_set_password",
      phone_number: phoneNumber,
      password,
      confirm_password: confirmPassword,
    }),
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.data?.message || "خطا در تنظیم رمز");
  return json.data as { message: string; redirect_url: string };
}

export function getLoginConfig() {
  return window.PuzzlingLoginConfig;
}
