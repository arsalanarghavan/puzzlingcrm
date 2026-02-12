import { useEffect, useState } from "react"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { getConfigOrNull } from "@/api/client"
import { getLogs, type LogEntry } from "@/api/logs"
import { Loader2, History, Settings, CheckCircle } from "lucide-react"

export function LogsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [activeTab, setActiveTab] = useState("events")
  const [events, setEvents] = useState<LogEntry[]>([])
  const [systemLogs, setSystemLogs] = useState<LogEntry[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [eventsPage, setEventsPage] = useState(1)
  const [systemPage, setSystemPage] = useState(1)
  const [eventsTotalPages, setEventsTotalPages] = useState(1)
  const [systemTotalPages, setSystemTotalPages] = useState(1)

  const loadEvents = async (page = 1) => {
    setLoading(true)
    setError(null)
    try {
      const res = await getLogs({ log_tab: "events", paged: page })
      if (res.success && res.data) {
        const d = res.data as { logs: LogEntry[]; total_pages: number }
        setEvents(d.logs ?? [])
        setEventsTotalPages(d.total_pages ?? 1)
        setEventsPage(page)
      } else {
        setError(res.message ?? "خطا در بارگذاری لاگ‌ها")
      }
    } catch {
      setError("خطا در بارگذاری لاگ‌ها")
    } finally {
      setLoading(false)
    }
  }

  const loadSystem = async (page = 1) => {
    setLoading(true)
    setError(null)
    try {
      const res = await getLogs({ log_tab: "system", paged: page })
      if (res.success && res.data) {
        const d = res.data as { logs: LogEntry[]; total_pages: number }
        setSystemLogs(d.logs ?? [])
        setSystemTotalPages(d.total_pages ?? 1)
        setSystemPage(page)
      } else {
        setError(res.message ?? "خطا در بارگذاری لاگ‌ها")
      }
    } catch {
      setError("خطا در بارگذاری لاگ‌ها")
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    if (activeTab === "events") loadEvents(1)
    else loadSystem(1)
  }, [activeTab])

  const renderEventsTable = () => (
    <>
      <h3 className="flex items-center gap-2 mb-4">
        <History className="h-5 w-5" />
        لاگ رویدادهای سیستم
      </h3>
      {loading ? (
        <div className="flex justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : events.length === 0 ? (
        <p className="text-muted-foreground py-8">هیچ لاگی برای نمایش وجود ندارد.</p>
      ) : (
        <>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>رویداد</TableHead>
                <TableHead>جزئیات</TableHead>
                <TableHead>کاربر</TableHead>
                <TableHead>تاریخ</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {events.map((log) => (
                <TableRow key={log.id}>
                  <TableCell className="font-medium">{log.title}</TableCell>
                  <TableCell className="max-w-md truncate" title={log.content}>
                    {log.content}
                  </TableCell>
                  <TableCell>{log.author}</TableCell>
                  <TableCell>{log.date}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {eventsTotalPages > 1 && (
            <div className="flex gap-2 justify-center mt-4">
              <Button
                variant="outline"
                size="sm"
                disabled={eventsPage <= 1}
                onClick={() => loadEvents(eventsPage - 1)}
              >
                قبلی
              </Button>
              <span className="flex items-center px-2">
                {eventsPage} / {eventsTotalPages}
              </span>
              <Button
                variant="outline"
                size="sm"
                disabled={eventsPage >= eventsTotalPages}
                onClick={() => loadEvents(eventsPage + 1)}
              >
                بعدی
              </Button>
            </div>
          )}
        </>
      )}
    </>
  )

  const renderSystemTable = () => (
    <>
      <h3 className="flex items-center gap-2 mb-2">
        <Settings className="h-5 w-5" />
        لاگ خطاهای سیستم
      </h3>
      <p className="text-sm text-muted-foreground mb-4">
        در این بخش خطاهای سیستمی، مشکلات مربوط به API و سایر باگ‌های ثبت شده نمایش داده
        می‌شود.
      </p>
      {loading ? (
        <div className="flex justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : systemLogs.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <CheckCircle className="h-16 w-16 text-green-500 mb-4" />
          <h4 className="font-medium mb-2">سیستم در وضعیت پایدار است</h4>
          <p className="text-muted-foreground">هیچ خطای سیستمی برای نمایش وجود ندارد.</p>
        </div>
      ) : (
        <>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>عنوان خطا</TableHead>
                <TableHead>جزئیات</TableHead>
                <TableHead>تاریخ</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {systemLogs.map((log) => (
                <TableRow key={log.id}>
                  <TableCell className="font-medium">{log.title}</TableCell>
                  <TableCell className="max-w-md" title={log.content}>
                    <span className="line-clamp-2">{log.content}</span>
                  </TableCell>
                  <TableCell>{log.date}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {systemTotalPages > 1 && (
            <div className="flex gap-2 justify-center mt-4">
              <Button
                variant="outline"
                size="sm"
                disabled={systemPage <= 1}
                onClick={() => loadSystem(systemPage - 1)}
              >
                قبلی
              </Button>
              <span className="flex items-center px-2">
                {systemPage} / {systemTotalPages}
              </span>
              <Button
                variant="outline"
                size="sm"
                disabled={systemPage >= systemTotalPages}
                onClick={() => loadSystem(systemPage + 1)}
              >
                بعدی
              </Button>
            </div>
          )}
        </>
      )}
    </>
  )

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">لاگ‌های سیستم</h1>
      </div>
      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      <Card>
        <CardContent className="pt-6">
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList>
              <TabsTrigger value="events">لاگ رویدادها</TabsTrigger>
              <TabsTrigger value="system">لاگ سیستم</TabsTrigger>
            </TabsList>
            <TabsContent value="events" className="mt-4">
              {renderEventsTable()}
            </TabsContent>
            <TabsContent value="system" className="mt-4">
              {renderSystemTable()}
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  )
}
