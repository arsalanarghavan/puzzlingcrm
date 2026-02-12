import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import { getConfigOrNull, apiPost } from "@/api/client"
import {
  FolderOpen,
  CheckCircle,
  ListTodo,
  Headphones,
  TrendingUp,
  Filter,
  Share2,
  ArrowLeft,
  Eye,
  Pencil,
  Trash2,
  UserPlus,
  Mail,
} from "lucide-react"

export interface DashboardStats {
  total_projects: number
  new_projects_this_month?: number
  new_projects_growth?: number
  completed_projects?: number
  in_progress_projects: number
  pending_projects?: number
  total_tasks: number
  completed_tasks: number
  overdue_tasks?: number
  customer_count: number
  open_tickets: number
  income_this_month: number
  total_revenue: number
  revenue_growth?: number
  completion_rate?: number
}

interface DashboardFullData {
  stats: DashboardStats
  team: Array<{
    id: number
    name: string
    avatar: string
    role: string
    total_tasks: number
    completed_tasks: number
    progress: number
    is_online: boolean
  }>
  running_projects: Array<{
    id: number
    title: string
    excerpt: string
    progress: number
    done: number
    total: number
    assigned: number[]
    modified: string
  }>
  daily_tasks: Array<{
    id: number
    title: string
    due_date: string
    due_time: string
    project: string
  }>
  projects_table: Array<{
    id: number
    title: string
    done_tasks: number
    total_tasks: number
    progress: number
    status: string
    status_slug: string
    assigned: number[]
    due_date: string
  }>
  revenue_data: { values: number[]; labels: string[] }
  monthly_goals: { new_projects: number; completed: number; pending: number }
}

function StatCard({
  title,
  value,
  description,
  icon: Icon,
}: {
  title: string
  value: string | number
  description?: string
  icon: React.ComponentType<{ className?: string }>
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        {description != null && (
          <p className="text-xs text-muted-foreground">{description}</p>
        )}
      </CardContent>
    </Card>
  )
}

function formatNumber(n: number, fa?: boolean): string {
  if (fa) return n.toLocaleString("fa-IR")
  return n.toLocaleString()
}

function SimpleBarChart({ data, labels, max }: { data: number[]; labels: string[]; max?: number }) {
  const m = max ?? Math.max(...data, 1)
  return (
    <div className="flex h-[200px] items-end gap-1">
      {data.map((v, i) => (
        <div key={i} className="flex flex-1 flex-col items-center gap-1">
          <div
            className="w-full min-h-[4px] rounded-t bg-primary transition-all"
            style={{ height: `${(v / m) * 100}%` }}
          />
          <span className="text-[10px] text-muted-foreground truncate max-w-full" title={labels[i]}>{labels[i]}</span>
        </div>
      ))}
    </div>
  )
}

function SimpleDoughnut({ values, labels }: { values: number[]; labels: string[] }) {
  const total = values.reduce((a, b) => a + b, 0) || 1
  const stops = values.reduce<{ pct: number; color: string }[]>((acc, v, i) => {
    const from = acc.length ? acc[acc.length - 1].pct : 0
    acc.push({ pct: from + (v / total) * 100, color: ["hsl(var(--primary))", "#22c55e", "#eab308"][i] || "#6b7280" })
    return acc
  }, [])
  const gradient = stops.map((s, i) => {
    const from = i === 0 ? 0 : stops[i - 1].pct
    return `${s.color} ${from}% ${s.pct}%`
  }).join(", ")
  return (
    <div className="flex h-[200px] flex-col items-center justify-center gap-2">
      <div
        className="h-32 w-32 rounded-full"
        style={{ background: `conic-gradient(from 0deg, ${gradient})` }}
      />
      <div className="flex flex-wrap justify-center gap-2 text-xs">
        {labels.map((l, i) => (
          <span key={i} className="flex items-center gap-1">
            <span
              className="h-2 w-2 rounded-full"
              style={{ background: ["hsl(var(--primary))", "#22c55e", "#eab308"][i] || "#6b7280" }}
            />
            {l}: {formatNumber(values[i])}
          </span>
        ))}
      </div>
    </div>
  )
}

function DashboardSystemManager() {
  const [data, setData] = useState<DashboardFullData | null>(null)
  const [loading, setLoading] = useState(true)
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true

  useEffect(() => {
    let cancelled = false
    apiPost<DashboardFullData>("puzzlingcrm_get_dashboard_full", {})
      .then((res) => {
        if (cancelled) return
        if (res.success && res.data) setData(res.data)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => { cancelled = true }
  }, [])

  const s = data?.stats ?? {
    total_projects: 0,
    new_projects_this_month: 0,
    new_projects_growth: 0,
    completed_projects: 0,
    in_progress_projects: 0,
    pending_projects: 0,
    total_tasks: 0,
    completed_tasks: 0,
    customer_count: 0,
    open_tickets: 0,
    income_this_month: 0,
    total_revenue: 0,
    revenue_growth: 0,
    completion_rate: 0,
  }
  const team = data?.team ?? []
  const running = data?.running_projects ?? []
  const daily = data?.daily_tasks ?? []
  const projectsTable = data?.projects_table ?? []
  const revenue = data?.revenue_data ?? { values: [], labels: [] }
  const goals = data?.monthly_goals ?? { new_projects: 0, completed: 0, pending: 0 }

  if (loading) {
    return (
      <div className="space-y-4">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <Card key={i}>
              <CardContent className="pt-6">
                <p className="text-sm text-muted-foreground">در حال بارگذاری...</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="grid gap-4 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-4">
          {/* Banner */}
          <Card className="overflow-hidden bg-gradient-to-br from-primary to-primary/80 text-primary-foreground">
            <CardContent className="flex flex-row items-center justify-between p-4">
              <div>
                <h4 className="font-medium">مدیریت پروژه</h4>
                <p className="text-sm opacity-90">پروژه‌ها را با یک کلیک مدیریت کنید.</p>
                <Button asChild size="sm" className="mt-2" variant="secondary">
                  <Link to="/projects">
                    همین الان مدیریت کنید <ArrowLeft className="h-4 w-4" />
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Team Table */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>تیم</CardTitle>
              <Button asChild variant="outline" size="sm">
                <Link to="/staff">مشاهده همه</Link>
              </Button>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>نام</TableHead>
                    <TableHead>وظایف</TableHead>
                    <TableHead>وضعیت</TableHead>
                    <TableHead>پیشرفت</TableHead>
                    <TableHead>عملیات</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {team.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center text-muted-foreground">
                        موردی یافت نشد
                      </TableCell>
                    </TableRow>
                  ) : (
                    team.map((m) => (
                      <TableRow key={m.id}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Avatar className="h-8 w-8">
                              <AvatarImage src={m.avatar} />
                              <AvatarFallback>{m.name.slice(0, 2)}</AvatarFallback>
                            </Avatar>
                            <div>
                              <div className="font-medium">{m.name}</div>
                              <div className="text-xs text-muted-foreground">{m.role}</div>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell>{formatNumber(m.total_tasks, isRtl)}</TableCell>
                        <TableCell>
                          <Badge variant={m.is_online ? "default" : "secondary"}>
                            {m.is_online ? "آنلاین" : "آفلاین"}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          {formatNumber(m.completed_tasks, isRtl)} / {formatNumber(m.total_tasks, isRtl)}
                        </TableCell>
                        <TableCell>
                          <div className="flex gap-1">
                            <Button variant="ghost" size="icon"><UserPlus className="h-4 w-4" /></Button>
                            <Button variant="ghost" size="icon" asChild>
                              <a href={`mailto:${m.id}`}><Mail className="h-4 w-4" /></a>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                              <Link to={`/staff?user=${m.id}`}><Eye className="h-4 w-4" /></Link>
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>

        {/* KPI Cards */}
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <StatCard
              title="پروژه‌های جدید"
              value={formatNumber(s.new_projects_this_month ?? 0, isRtl)}
              description={`${(s.new_projects_growth ?? 0) >= 0 ? "+" : ""}${formatNumber(Math.round(s.new_projects_growth ?? 0), isRtl)}٪`}
              icon={FolderOpen}
            />
            <StatCard
              title="تکمیل‌شده"
              value={formatNumber(s.completed_projects ?? 0, isRtl)}
              icon={CheckCircle}
            />
            <StatCard
              title="در حال انجام"
              value={formatNumber(s.in_progress_projects ?? 0, isRtl)}
              icon={FolderOpen}
            />
            <StatCard
              title="در انتظار"
              value={formatNumber(s.pending_projects ?? 0, isRtl)}
              icon={ListTodo}
            />
          </div>
        </div>
      </div>

      {/* Row 2: Charts and Running Projects */}
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>آمار پروژه</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="mb-4 flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">کل درآمد</p>
                <p className="text-xl font-bold">{formatNumber(s.total_revenue ?? 0, isRtl)}</p>
              </div>
            </div>
            <SimpleBarChart data={revenue.values} labels={revenue.labels} />
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>پروژه‌های در حال اجرا</CardTitle>
            <Button asChild variant="outline" size="sm">
              <Link to="/projects">مشاهده همه</Link>
            </Button>
          </CardHeader>
          <CardContent>
            {running.length === 0 ? (
              <p className="text-center text-sm text-muted-foreground py-4">پروژه فعالی وجود ندارد</p>
            ) : (
              <div className="space-y-4">
                {running.map((p) => (
                  <div key={p.id} className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <Link to={`/projects/${p.id}`} className="font-medium hover:underline">{p.title}</Link>
                      <span className="text-muted-foreground">{p.progress}٪</span>
                    </div>
                    <Progress value={p.progress} />
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Row 3: Monthly Goals, Daily Tasks */}
      <div className="grid gap-4 lg:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>اهداف ماهانه</CardTitle>
            <Button asChild variant="outline" size="sm">
              <Link to="/reports">مشاهده همه</Link>
            </Button>
          </CardHeader>
          <CardContent>
            <SimpleDoughnut
              values={[goals.new_projects, goals.completed, goals.pending]}
              labels={["جدید", "تکمیل‌شده", "در انتظار"]}
            />
          </CardContent>
        </Card>
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>وظایف روزانه</CardTitle>
          </CardHeader>
          <CardContent>
            {daily.length === 0 ? (
              <p className="text-center text-sm text-muted-foreground py-4">وظیفه‌ای برای امروز تعیین نشده</p>
            ) : (
              <div className="space-y-2">
                {daily.map((t) => (
                  <div key={t.id} className="flex items-center justify-between rounded-lg border p-3">
                    <div>
                      <p className="font-medium">{t.title}</p>
                      {t.project && <Badge variant="outline" className="mt-1">{t.project}</Badge>}
                    </div>
                    <span className="text-sm text-muted-foreground">{t.due_time || t.due_date}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Row 4: Projects Table + Tasks Summary */}
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>خلاصه پروژه‌ها</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>#</TableHead>
                  <TableHead>عنوان</TableHead>
                  <TableHead>وظایف</TableHead>
                  <TableHead>پیشرفت</TableHead>
                  <TableHead>وضعیت</TableHead>
                  <TableHead>تاریخ</TableHead>
                  <TableHead>عملیات</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {projectsTable.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center text-muted-foreground">موردی یافت نشد</TableCell>
                  </TableRow>
                ) : (
                  projectsTable.map((p, i) => (
                    <TableRow key={p.id}>
                      <TableCell>{formatNumber(i + 1, isRtl)}</TableCell>
                      <TableCell className="font-medium">{p.title}</TableCell>
                      <TableCell>{formatNumber(p.done_tasks, isRtl)} / {formatNumber(p.total_tasks, isRtl)}</TableCell>
                      <TableCell>
                        <Progress value={p.progress} className="w-20" />
                      </TableCell>
                      <TableCell><Badge variant="outline">{p.status}</Badge></TableCell>
                      <TableCell>{p.due_date || "-"}</TableCell>
                      <TableCell>
                        <div className="flex gap-1">
                          <Button variant="ghost" size="icon" asChild>
                            <Link to={`/projects/${p.id}`}><Eye className="h-4 w-4" /></Link>
                          </Button>
                          <Button variant="ghost" size="icon"><Pencil className="h-4 w-4" /></Button>
                          <Button variant="ghost" size="icon"><Trash2 className="h-4 w-4" /></Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>خلاصه وظایف</CardTitle>
            <Button asChild variant="outline" size="sm">
              <Link to="/tasks">مشاهده همه</Link>
            </Button>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div>
                <p className="text-sm text-muted-foreground">نرخ تکمیل</p>
                <p className="text-2xl font-bold">{formatNumber(Math.round(s.completion_rate ?? 0), isRtl)}٪</p>
              </div>
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span>انجام شده</span>
                  <span>{formatNumber(s.completed_tasks ?? 0, isRtl)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span>در حال انجام</span>
                  <span>{formatNumber((s.total_tasks ?? 0) - (s.completed_tasks ?? 0), isRtl)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span>معوق</span>
                  <span>{formatNumber(s.overdue_tasks ?? 0, isRtl)}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

export interface TeamMemberStats {
  total_tasks: number
  completed_tasks: number
  in_progress_tasks: number
  overdue_tasks: number
  today_tasks: number
  total_projects: number
  completion_rate: number
  total_tickets: number
  open_tickets: number
}

function DashboardTeamMember() {
  const [stats, setStats] = useState<TeamMemberStats | null>(null)
  const [loading, setLoading] = useState(true)
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true

  useEffect(() => {
    let cancelled = false
    apiPost<TeamMemberStats>("puzzlingcrm_get_team_member_stats", {})
      .then((res) => {
        if (cancelled) return
        if (res.success && res.data) setStats(res.data)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => { cancelled = true }
  }, [])

  const s = stats ?? {
    total_tasks: 0,
    completed_tasks: 0,
    in_progress_tasks: 0,
    overdue_tasks: 0,
    today_tasks: 0,
    total_projects: 0,
    completion_rate: 0,
    total_tickets: 0,
    open_tickets: 0,
  }

  if (loading) {
    return (
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {[1, 2, 3].map((i) => (
          <Card key={i}>
            <CardContent className="pt-6">
              <p className="text-sm text-muted-foreground">در حال بارگذاری...</p>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3" dir={isRtl ? "rtl" : "ltr"}>
      <StatCard
        title="وظایف من"
        value={formatNumber(s.total_tasks, isRtl)}
        description={s.completion_rate != null ? `${formatNumber(Math.round(s.completion_rate), isRtl)}٪ انجام شده` : undefined}
        icon={ListTodo}
      />
      <StatCard
        title="وظایف انجام‌شده"
        value={formatNumber(s.completed_tasks, isRtl)}
        icon={CheckCircle}
      />
      <StatCard
        title="وظایف در حال انجام"
        value={formatNumber(s.in_progress_tasks, isRtl)}
        icon={FolderOpen}
      />
      <StatCard
        title="وظایف معوق"
        value={formatNumber(s.overdue_tasks, isRtl)}
        icon={ListTodo}
      />
      <StatCard
        title="پروژه‌های من"
        value={formatNumber(s.total_projects, isRtl)}
        icon={FolderOpen}
      />
      <StatCard
        title="تیکت‌های باز"
        value={formatNumber(s.open_tickets, isRtl)}
        icon={Headphones}
      />
    </div>
  )
}

export interface ClientStats {
  total_projects: number
  active_projects: number
  completed_projects: number
  total_tickets: number
  open_tickets: number
  total_contracts: number
  total_value: number
  paid_amount: number
  pending_amount: number
}

function DashboardClient() {
  const [stats, setStats] = useState<ClientStats | null>(null)
  const [loading, setLoading] = useState(true)
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true

  useEffect(() => {
    let cancelled = false
    apiPost<ClientStats>("puzzlingcrm_get_client_stats", {})
      .then((res) => {
        if (cancelled) return
        if (res.success && res.data) setStats(res.data)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => { cancelled = true }
  }, [])

  const s = stats ?? {
    total_projects: 0,
    active_projects: 0,
    completed_projects: 0,
    total_tickets: 0,
    open_tickets: 0,
    total_contracts: 0,
    total_value: 0,
    paid_amount: 0,
    pending_amount: 0,
  }

  if (loading) {
    return (
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {[1, 2, 3].map((i) => (
          <Card key={i}>
            <CardContent className="pt-6">
              <p className="text-sm text-muted-foreground">در حال بارگذاری...</p>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3" dir={isRtl ? "rtl" : "ltr"}>
      <StatCard
        title="پروژه‌های من"
        value={formatNumber(s.total_projects, isRtl)}
        description={s.active_projects != null ? `${formatNumber(s.active_projects, isRtl)} فعال` : undefined}
        icon={FolderOpen}
      />
      <StatCard
        title="قراردادها"
        value={formatNumber(s.total_contracts, isRtl)}
        icon={CheckCircle}
      />
      <StatCard
        title="تیکت‌های باز"
        value={formatNumber(s.open_tickets, isRtl)}
        icon={Headphones}
      />
      <StatCard
        title="مبلغ کل قراردادها"
        value={formatNumber(s.total_value, isRtl)}
        icon={TrendingUp}
      />
      <StatCard
        title="پرداخت‌شده"
        value={formatNumber(s.paid_amount, isRtl)}
        icon={CheckCircle}
      />
      <StatCard
        title="مانده پرداخت"
        value={formatNumber(s.pending_amount, isRtl)}
        icon={ListTodo}
      />
    </div>
  )
}

export function DashboardPage() {
  const config = getConfigOrNull()
  const roleSlug = config?.user?.roleSlug ?? "client"
  const isRtl = config?.isRtl === true

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">داشبورد</h1>
        {roleSlug === "system_manager" && (
          <div className="flex gap-2">
            <Button variant="outline" size="sm">
              <Filter className="h-4 w-4" /> فیلتر
            </Button>
            <Button size="sm">
              <Share2 className="h-4 w-4" /> اشتراک
            </Button>
          </div>
        )}
      </div>
      {roleSlug === "system_manager" && <DashboardSystemManager />}
      {roleSlug === "team_member" && <DashboardTeamMember />}
      {(roleSlug === "client" || roleSlug === "finance_manager" || !roleSlug) && <DashboardClient />}
    </div>
  )
}
