import { useState, useEffect } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { getConfigOrNull } from "@/api/client"
import { accountingSettingsGet, accountingSettingsSave, accountingSeedChart, accountingFiscalYears } from "@/api/accounting"
import { Settings, Loader2 } from "lucide-react"

export function AccountingSettingsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [currency, setCurrency] = useState("rial")
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [fiscalYears, setFiscalYears] = useState<{ id: number; name: string }[]>([])
  const [loading, setLoading] = useState(false)
  const [seedLoading, setSeedLoading] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    accountingSettingsGet().then((res) => {
      if (res.success && res.data) {
        setCurrency(res.data.currency ?? "rial")
        setFiscalYearId(res.data.fiscal_year_id ?? 0)
      }
    })
    accountingFiscalYears().then((res) => {
      if (res.success && res.data?.items) setFiscalYears(res.data.items)
    })
  }, [])

  const save = () => {
    setLoading(true)
    setMessage(null)
    accountingSettingsSave({ currency, fiscal_year_id: fiscalYearId }).then((res) => {
      setLoading(false)
      setMessage(res.success ? "ذخیره شد." : (res.message ?? "خطا"))
    })
  }

  const seed = () => {
    setSeedLoading(true)
    setMessage(null)
    accountingSeedChart(fiscalYearId || undefined).then((res) => {
      setSeedLoading(false)
      if (res.success && res.data?.count !== undefined) {
        setMessage(`کدینگ پیش‌فرض بارگذاری شد (${res.data.count} حساب).`)
      } else {
        setMessage(res.message ?? "خطا در بارگذاری کدینگ.")
      }
    })
  }

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <Settings className="h-5 w-5" />
          تنظیمات حسابداری
        </h2>
        <p className="text-muted-foreground text-sm">واحد پول، سال مالی پیش‌فرض، کدینگ استاندارد ایران</p>
      </div>

      {message && (
        <p className="text-sm text-muted-foreground">{message}</p>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">تنظیمات عمومی</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <Label>واحد پول</Label>
            <select
              className="w-full mt-1 border rounded px-2 py-1.5 text-sm max-w-xs"
              value={currency}
              onChange={(e) => setCurrency(e.target.value)}
            >
              <option value="rial">ریال</option>
              <option value="toman">تومان</option>
            </select>
          </div>
          <div>
            <Label>سال مالی پیش‌فرض</Label>
            <select
              className="w-full mt-1 border rounded px-2 py-1.5 text-sm max-w-xs"
              value={fiscalYearId}
              onChange={(e) => setFiscalYearId(Number(e.target.value))}
            >
              <option value={0}>—</option>
              {fiscalYears.map((fy) => (
                <option key={fy.id} value={fy.id}>{fy.name}</option>
              ))}
            </select>
          </div>
          <Button onClick={save} disabled={loading}>
            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            ذخیره تنظیمات
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">کدینگ پیش‌فرض ایران</CardTitle>
          <p className="text-sm text-muted-foreground">بارگذاری نمودار حساب‌های استاندارد (گروه‌های ۱ تا ۷) برای سال مالی انتخاب‌شده.</p>
        </CardHeader>
        <CardContent>
          <Button variant="outline" onClick={seed} disabled={seedLoading || !fiscalYearId}>
            {seedLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
            بارگذاری کدینگ پیش‌فرض
          </Button>
        </CardContent>
      </Card>
    </div>
  )
}
