import { useState, useEffect } from "react"
import { Link } from "react-router-dom"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { getConfigOrNull } from "@/api/client"
import { accountingFiscalYears, accountingJournalList } from "@/api/accounting"
import { Calculator, BookOpen, FileText, Book, BarChart2, Calendar, Settings, Users, Package, ListOrdered, Wallet, Receipt, Banknote } from "lucide-react"

const LINKS = [
  { to: "/accounting/persons", label: "اشخاص (طرف‌های حساب)", icon: Users },
  { to: "/accounting/products", label: "کالا و خدمات", icon: Package },
  { to: "/accounting/invoices", label: "فاکتورها", icon: ListOrdered },
  { to: "/accounting/cash-accounts", label: "حساب‌های بانک/صندوق", icon: Wallet },
  { to: "/accounting/receipts", label: "رسید و پرداخت", icon: Receipt },
  { to: "/accounting/checks", label: "چک‌ها", icon: Banknote },
  { to: "/accounting/chart", label: "نمودار حساب‌ها", icon: BookOpen },
  { to: "/accounting/journals", label: "اسناد حسابداری", icon: FileText },
  { to: "/accounting/ledger", label: "دفتر کل / معین", icon: Book },
  { to: "/accounting/reports", label: "گزارشات مالی", icon: BarChart2 },
  { to: "/accounting/fiscal-year", label: "سال مالی", icon: Calendar },
  { to: "/accounting/settings", label: "تنظیمات حسابداری", icon: Settings },
]

export function AccountingDashboardPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [fiscalYears, setFiscalYears] = useState<{ id: number; name: string }[]>([])
  const [recentTotal, setRecentTotal] = useState<number>(0)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    async function load() {
      const res = await accountingFiscalYears()
      if (cancelled) return
      if (res.success && res.data?.items) setFiscalYears(res.data.items)
      const listRes = await accountingJournalList({ per_page: 5, page: 1 })
      if (cancelled) return
      if (listRes.success && listRes.data?.total !== undefined) setRecentTotal(listRes.data.total)
      setLoading(false)
    }
    load()
    return () => { cancelled = true }
  }, [])

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <Calculator className="h-5 w-5" />
          داشبورد حسابداری
        </h2>
        <p className="text-muted-foreground text-sm">خلاصه و دسترسی سریع به بخش‌های حسابداری</p>
      </div>

      {loading ? (
        <p className="text-muted-foreground">در حال بارگذاری...</p>
      ) : (
        <>
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle className="text-base">سال‌های مالی</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-2xl font-semibold">{fiscalYears.length}</p>
                <Button variant="outline" size="sm" className="mt-2" asChild>
                  <Link to="/accounting/fiscal-year">مدیریت سال مالی</Link>
                </Button>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle className="text-base">تعداد اسناد</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-2xl font-semibold">{recentTotal}</p>
                <Button variant="outline" size="sm" className="mt-2" asChild>
                  <Link to="/accounting/journals">مشاهده اسناد</Link>
                </Button>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">دسترسی سریع</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                {LINKS.map(({ to, label, icon: Icon }) => (
                  <Button key={to} variant="outline" className="justify-start" asChild>
                    <Link to={to}>
                      <Icon className="h-4 w-4 ml-2" />
                      {label}
                    </Link>
                  </Button>
                ))}
              </div>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  )
}
