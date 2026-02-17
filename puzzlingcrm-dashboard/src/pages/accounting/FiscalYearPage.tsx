import { useState, useEffect } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { getConfigOrNull } from "@/api/client"
import { accountingFiscalYears } from "@/api/accounting"
import type { FiscalYear } from "@/api/accounting"
import { Calendar, Loader2 } from "lucide-react"

export function FiscalYearPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [items, setItems] = useState<FiscalYear[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    accountingFiscalYears().then((res) => {
      if (res.success && res.data?.items) setItems(res.data.items)
      setLoading(false)
    })
  }, [])

  return (
    <div className="space-y-6" dir={isRtl ? "rtl" : "ltr"}>
      <div>
        <h2 className="text-xl font-semibold tracking-tight flex items-center gap-2">
          <Calendar className="h-5 w-5" />
          سال مالی
        </h2>
        <p className="text-muted-foreground text-sm">تعریف و مدیریت سال‌های مالی</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">لیست سال‌های مالی</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center gap-2 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              در حال بارگذاری...
            </div>
          ) : items.length === 0 ? (
            <p className="text-muted-foreground">سال مالی تعریف نشده. از طریق API یا تنظیمات حسابداری می‌توانید سال مالی اضافه کنید.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>نام</TableHead>
                  <TableHead>از تاریخ</TableHead>
                  <TableHead>تا تاریخ</TableHead>
                  <TableHead>وضعیت</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell>{row.name}</TableCell>
                    <TableCell>{row.start_date}</TableCell>
                    <TableCell>{row.end_date}</TableCell>
                    <TableCell>
                      {row.is_active ? <Badge>فعال</Badge> : <Badge variant="secondary">غیرفعال</Badge>}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
