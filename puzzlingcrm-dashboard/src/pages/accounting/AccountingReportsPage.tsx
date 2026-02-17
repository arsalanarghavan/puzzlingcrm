import { useState, useEffect } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { getConfigOrNull } from "@/api/client"
import {
  accountingReportTrialBalance,
  accountingReportBalanceSheet,
  accountingReportProfitLoss,
  accountingFiscalYears,
} from "@/api/accounting"
import { BarChart2, Loader2 } from "lucide-react"

export function AccountingReportsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [fiscalYears, setFiscalYears] = useState<{ id: number; name: string }[]>([])
  const [fiscalYearId, setFiscalYearId] = useState<number>(0)
  const [dateFrom, setDateFrom] = useState("")
  const [dateTo, setDateTo] = useState("")
  const [asOfDate, setAsOfDate] = useState("")
  const [trialBalance, setTrialBalance] = useState<Array<{ code: string; title: string; balance_debit: number; balance_credit: number }>>([])
  const [balanceSheet, setBalanceSheet] = useState<{ assets: Array<{ code: string; title: string; balance: number }>; liabilities: Array<{ code: string; title: string; balance: number }>; equity: Array<{ code: string; title: string; balance: number }> } | null>(null)
  const [profitLoss, setProfitLoss] = useState<{ income: unknown[]; expense: unknown[]; income_total: number; expense_total: number; net: number } | null>(null)
  const [loading, setLoading] = useState(false)
  const [activeTab, setActiveTab] = useState("trial")

  useEffect(() => {
    accountingFiscalYears().then((res) => {
      if (res.success && res.data?.items?.length) {
        setFiscalYears(res.data.items)
        if (!fiscalYearId && res.data.items[0]) setFiscalYearId(res.data.items[0].id)
      }
    })
  }, [])

  const loadTrialBalance = () => {
    if (!fiscalYearId) return
    setLoading(true)
    accountingReportTrialBalance(fiscalYearId, dateFrom || undefined, dateTo || undefined).then((res) => {
      setLoading(false)
      if (res.success && Array.isArray(res.data)) setTrialBalance(res.data)
      else setTrialBalance([])
    })
  }

  const loadBalanceSheet = () => {
    if (!fiscalYearId) return
    setLoading(true)
    accountingReportBalanceSheet(fiscalYearId, asOfDate || undefined).then((res) => {
      setLoading(false)
      if (res.success && res.data) setBalanceSheet(res.data as typeof balanceSheet)
      else setBalanceSheet(null)
    })
  }

  const loadProfitLoss = () => {
    if (!fiscalYearId) return
    setLoading(true)
    accountingReportProfitLoss(fiscalYearId, dateFrom || undefined, dateTo || undefined).then((res) => {
      setLoading(false)
      if (res.success && res.data) setProfitLoss(res.data as typeof profitLoss)
      else setProfitLoss(null)
    })
  }

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <BarChart2 className="h-5 w-5" />
          گزارشات مالی
        </h2>
        <p className="text-muted-foreground text-sm">تراز آزمایشی، ترازنامه، سود و زیان</p>
      </div>

      {fiscalYears.length > 0 && (
        <div className="flex flex-wrap gap-4 items-center">
          <div>
            <label className="text-sm ml-1">سال مالی:</label>
            <select
              className="border rounded px-2 py-1 text-sm mr-2"
              value={fiscalYearId}
              onChange={(e) => setFiscalYearId(Number(e.target.value))}
            >
              {fiscalYears.map((fy) => (
                <option key={fy.id} value={fy.id}>{fy.name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-sm ml-1">از تاریخ:</label>
            <input type="date" className="border rounded px-2 py-1 text-sm mr-2" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
          </div>
          <div>
            <label className="text-sm ml-1">تا تاریخ:</label>
            <input type="date" className="border rounded px-2 py-1 text-sm mr-2" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
          </div>
          <div>
            <label className="text-sm ml-1">تاریخ ترازنامه:</label>
            <input type="date" className="border rounded px-2 py-1 text-sm mr-2" value={asOfDate} onChange={(e) => setAsOfDate(e.target.value)} />
          </div>
        </div>
      )}

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="trial">تراز آزمایشی</TabsTrigger>
          <TabsTrigger value="balance">ترازنامه</TabsTrigger>
          <TabsTrigger value="pl">سود و زیان</TabsTrigger>
        </TabsList>
        <TabsContent value="trial">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">تراز آزمایشی</CardTitle>
              <Button size="sm" onClick={loadTrialBalance} disabled={!fiscalYearId || loading}>
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                بارگذاری
              </Button>
            </CardHeader>
            <CardContent>
              {trialBalance.length === 0 && !loading ? (
                <p className="text-muted-foreground">فیلتر را تنظیم کرده و بارگذاری کنید.</p>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>کد</TableHead>
                      <TableHead>عنوان</TableHead>
                      <TableHead>مانده بدهکار</TableHead>
                      <TableHead>مانده بستانکار</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {trialBalance.map((row, i) => (
                      <TableRow key={i}>
                        <TableCell className="font-mono">{row.code}</TableCell>
                        <TableCell>{row.title}</TableCell>
                        <TableCell>{row.balance_debit}</TableCell>
                        <TableCell>{row.balance_credit}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>
        <TabsContent value="balance">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">ترازنامه</CardTitle>
              <Button size="sm" onClick={loadBalanceSheet} disabled={!fiscalYearId || loading}>
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                بارگذاری
              </Button>
            </CardHeader>
            <CardContent>
              {!balanceSheet && !loading ? (
                <p className="text-muted-foreground">بارگذاری کنید.</p>
              ) : balanceSheet ? (
                <div className="grid gap-4 md:grid-cols-3">
                  <div>
                    <h4 className="font-medium mb-2">دارایی‌ها</h4>
                    {balanceSheet.assets?.map((a: { code: string; title: string; balance: number }, i: number) => (
                      <p key={i} className="text-sm">{a.code} {a.title}: {a.balance}</p>
                    ))}
                  </div>
                  <div>
                    <h4 className="font-medium mb-2">بدهی‌ها</h4>
                    {balanceSheet.liabilities?.map((a: { code: string; title: string; balance: number }, i: number) => (
                      <p key={i} className="text-sm">{a.code} {a.title}: {a.balance}</p>
                    ))}
                  </div>
                  <div>
                    <h4 className="font-medium mb-2">حقوق صاحبان سهام</h4>
                    {balanceSheet.equity?.map((a: { code: string; title: string; balance: number }, i: number) => (
                      <p key={i} className="text-sm">{a.code} {a.title}: {a.balance}</p>
                    ))}
                  </div>
                </div>
              ) : null}
            </CardContent>
          </Card>
        </TabsContent>
        <TabsContent value="pl">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">سود و زیان</CardTitle>
              <Button size="sm" onClick={loadProfitLoss} disabled={!fiscalYearId || loading}>
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                بارگذاری
              </Button>
            </CardHeader>
            <CardContent>
              {!profitLoss && !loading ? (
                <p className="text-muted-foreground">بارگذاری کنید.</p>
              ) : profitLoss ? (
                <div>
                  <p className="font-medium">جمع درآمد: {profitLoss.income_total}</p>
                  <p className="font-medium">جمع هزینه: {profitLoss.expense_total}</p>
                  <p className="font-semibold">سود (زیان) خالص: {profitLoss.net}</p>
                </div>
              ) : null}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
