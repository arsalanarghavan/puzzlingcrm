import { useCallback, useEffect, useState } from "react"
import { Link, useNavigate, useSearchParams } from "react-router-dom"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { DatePicker } from "@/components/ui/date-picker"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
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
  getContracts,
  getContract,
  manageContract,
  deleteContract,
  cancelContract,
  addProjectToContract,
  getProjectTemplates,
  getContractProducts,
  getProductProjectsPreview,
  getProjectAssignees,
  type Contract,
  type ContractDetail,
  type ProjectTemplate,
  type WcProduct,
} from "@/api/contracts"
import {
  getLeadForContract,
  createCustomerFromLead,
  type LeadForContract,
} from "@/api/leads"
import { cn } from "@/lib/utils"
import { Plus, Loader2, Pencil, Trash2, List, XCircle, ChevronRight, ChevronLeft, Package, UserPlus } from "lucide-react"

function formatNumber(n: number) {
  return new Intl.NumberFormat("fa-IR").format(n)
}

export function ContractsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const initialTab = searchParams.get("action") === "new" ? "new" : "list"
  const editId = searchParams.get("contract_id") ? parseInt(searchParams.get("contract_id")!, 10) : null
  const fromLeadId = searchParams.get("from_lead") ? parseInt(searchParams.get("from_lead")!, 10) : null

  const [tab, setTab] = useState<"list" | "new">(initialTab)
  const [contracts, setContracts] = useState<Contract[]>([])
  const [customers, setCustomers] = useState<{ id: number; display_name: string }[]>([])
  const [totalPages, setTotalPages] = useState(1)
  const [currentPage, setCurrentPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [search, setSearch] = useState("")
  const [customerFilter, setCustomerFilter] = useState("")
  const [paymentStatusFilter, setPaymentStatusFilter] = useState("")
  const [editingContract, setEditingContract] = useState<ContractDetail | null>(null)
  const [relatedProjects, setRelatedProjects] = useState<{ id: number; title: string }[]>([])
  const [durations, setDurations] = useState<{ value: string; label: string }[]>([])
  const [models, setModels] = useState<{ value: string; label: string }[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [newProjectTitle, setNewProjectTitle] = useState("")
  const [projectTemplates, setProjectTemplates] = useState<ProjectTemplate[]>([])
  const [createProjectWithTemplate, setCreateProjectWithTemplate] = useState(false)
  const [projectTemplateId, setProjectTemplateId] = useState<string>("")
  const [wizardStep, setWizardStep] = useState(1)
  const [wcProducts, setWcProducts] = useState<WcProduct[]>([])
  const [wcActive, setWcActive] = useState(false)
  const [productId, setProductId] = useState(0)
  const [projectTitlesPreview, setProjectTitlesPreview] = useState<string[]>([])
  const [projectAssignees, setProjectAssignees] = useState<{ id: number; display_name: string }[]>([])
  const [projectAssignments, setProjectAssignments] = useState<number[]>([])
  const [leadForContract, setLeadForContract] = useState<LeadForContract | null>(null)
  const [creatingCustomerFromLead, setCreatingCustomerFromLead] = useState(false)
  const [form, setForm] = useState({
    customer_id: 0,
    start_date: "",
    contract_title: "",
    total_amount: "",
    total_installments: 1,
    duration: "1-month",
    subscription_model: "onetime",
    installments: [] as { amount: string; due_date: string; status: string }[],
  })

  const loadList = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await getContracts({
        s: search || undefined,
        customer_filter: customerFilter ? parseInt(customerFilter, 10) : undefined,
        payment_status: paymentStatusFilter || undefined,
        paged: currentPage,
      })
      if (res.success && res.data) {
        setContracts(res.data.contracts ?? [])
        setCustomers(res.data.customers ?? [])
        setTotalPages(res.data.total_pages ?? 1)
        setCurrentPage(res.data.current_page ?? 1)
      } else {
        setError(res.message ?? "خطا در بارگذاری قراردادها")
      }
    } catch {
      setError("خطا در بارگذاری قراردادها")
    } finally {
      setLoading(false)
    }
  }, [search, customerFilter, paymentStatusFilter, currentPage])

  const loadContract = useCallback(async (id: number) => {
    setLoading(true)
    setError(null)
    try {
      const res = await getContract(id)
      if (res.success && res.data) {
        const c = res.data.contract
        setRelatedProjects(res.data.related_projects ?? [])
        setDurations(res.data.durations ?? [])
        setModels(res.data.models ?? [])
        if (c) {
          setEditingContract(c)
          setForm({
            customer_id: c.customer_id,
            start_date: c.start_date ? c.start_date.slice(0, 10) : "",
            contract_title: c.title ?? "",
            total_amount: c.total_amount ?? "",
            total_installments: c.total_installments ?? 1,
            duration: c.duration ?? "1-month",
            subscription_model: c.subscription_model ?? "onetime",
            installments: (c.installments ?? []).map((i) => ({
              amount: i.amount,
              due_date: i.due_date_gregorian ? i.due_date_gregorian.slice(0, 10) : i.due_date,
              status: i.status,
            })),
          })
        } else {
          setEditingContract(null)
          setError(res.message ?? "خطا در بارگذاری قرارداد")
        }
      } else {
        setError(res.message ?? "خطا در بارگذاری قرارداد")
      }
    } catch {
      setError("خطا در بارگذاری قرارداد")
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (tab === "list") {
      loadList()
    }
  }, [tab, loadList])

  useEffect(() => {
    if (fromLeadId && tab === "new") {
      getLeadForContract(fromLeadId).then((res) => {
        const d = res.success && res.data ? res.data : null
        if (d) {
          setLeadForContract(d)
          if (d.existing_customer_id > 0) {
            setForm((f) => ({ ...f, customer_id: d.existing_customer_id }))
          }
        }
      })
    } else {
      setLeadForContract(null)
    }
  }, [fromLeadId, tab])

  useEffect(() => {
    if (editId && tab === "new") {
      loadContract(editId)
    } else if (tab === "new" && !editId) {
      setEditingContract(null)
      setForm({
        customer_id: 0,
        start_date: new Date().toISOString().slice(0, 10),
        contract_title: "",
        total_amount: "",
        total_installments: 1,
        duration: "1-month",
        subscription_model: "onetime",
        installments: [],
      })
      setRelatedProjects([])
      getContracts({}).then((res) => {
        if (res.success && res.data) setCustomers(res.data.customers ?? [])
      })
      setDurations([
        { value: "1-month", label: "یک ماهه" },
        { value: "3-months", label: "سه ماهه" },
        { value: "6-months", label: "شش ماهه" },
        { value: "12-months", label: "یک ساله" },
      ])
      setModels([
        { value: "onetime", label: "یکبار پرداخت" },
        { value: "subscription", label: "اشتراکی" },
      ])
    }
  }, [tab, editId])

  useEffect(() => {
    if (tab === "new" && !editingContract) {
      getProjectTemplates().then((res) => {
        if (res.success && res.data?.templates) setProjectTemplates(res.data.templates)
      })
      getContractProducts().then((res) => {
        if (res.success && res.data) {
          setWcProducts(res.data.products ?? [])
          setWcActive(res.data.wc_active ?? false)
        }
      })
      getProjectAssignees().then((res) => {
        if (res.success && res.data?.users) setProjectAssignees(res.data.users)
      })
    }
  }, [tab, editingContract])

  useEffect(() => {
    if (productId > 0) {
      getProductProjectsPreview(productId).then((res) => {
        if (res.success && res.data?.titles) {
          setProjectTitlesPreview(res.data.titles)
          setProjectAssignments(res.data.titles.map(() => 0))
        }
      })
    } else {
      setProjectTitlesPreview([])
      setProjectAssignments([])
    }
  }, [productId])

  const openNew = () => {
    navigate("/contracts?action=new")
    setTab("new")
    setLeadForContract(null)
    setEditingContract(null)
    setCreateProjectWithTemplate(false)
    setProjectTemplateId("")
    setWizardStep(1)
    setProductId(0)
    setProjectTitlesPreview([])
    setProjectAssignments([])
  }

  const openEdit = (c: Contract) => {
    navigate(`/contracts?action=new&contract_id=${c.id}`)
    setTab("new")
    loadContract(c.id)
  }

  const backToList = () => {
    navigate("/contracts")
    setTab("list")
    loadList()
  }

  const addInstallmentRow = () => {
    setForm((f) => ({
      ...f,
      installments: [...f.installments, { amount: "", due_date: "", status: "pending" }],
    }))
  }

  const removeInstallmentRow = (idx: number) => {
    setForm((f) => ({
      ...f,
      installments: f.installments.filter((_, i) => i !== idx),
    }))
  }

  const updateInstallment = (idx: number, field: "amount" | "due_date" | "status", value: string) => {
    setForm((f) => {
      const next = [...f.installments]
      next[idx] = { ...next[idx], [field]: value }
      return { ...f, installments: next }
    })
  }

  const calculateInstallments = () => {
    const total = parseInt(form.total_amount.replace(/\D/g, ""), 10) || 0
    const count = form.total_installments || 1
    if (total <= 0 || count <= 0) return
    const perInstallment = Math.floor(total / count)
    const firstDate = form.start_date ? new Date(form.start_date) : new Date()
    const rows: { amount: string; due_date: string; status: string }[] = []
    for (let i = 0; i < count; i++) {
      const d = new Date(firstDate)
      d.setMonth(d.getMonth() + i)
      rows.push({
        amount: String(perInstallment),
        due_date: d.toISOString().slice(0, 10),
        status: "pending",
      })
    }
    setForm((f) => ({ ...f, installments: rows }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!form.customer_id || !form.start_date) {
      setError("مشتری و تاریخ شروع الزامی هستند.")
      return
    }
    if (form.installments.length === 0) {
      setError("حداقل یک قسط باید ثبت شود.")
      return
    }
    const hasInvalid = form.installments.some((i) => !i.amount || !i.due_date)
    if (hasInvalid) {
      setError("تمام اقساط باید مبلغ و تاریخ داشته باشند.")
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      const totalAmount = form.total_amount.replace(/\D/g, "") || String(form.installments.reduce((s, i) => s + (parseInt(i.amount.replace(/\D/g, ""), 10) || 0), 0))
      const res = await manageContract({
        contract_id: editingContract?.id,
        customer_id: form.customer_id,
        _project_start_date: form.start_date,
        contract_title: form.contract_title || undefined,
        total_amount: String(totalAmount),
        total_installments: form.installments.length,
        _project_contract_duration: form.duration,
        _project_subscription_model: form.subscription_model,
        payment_amount: form.installments.map((i) => i.amount.replace(/\D/g, "") || "0"),
        payment_due_date: form.installments.map((i) => i.due_date),
        payment_status: form.installments.map((i) => i.status),
        project_template_id:
          !editingContract && !productId && createProjectWithTemplate && projectTemplateId
            ? parseInt(projectTemplateId, 10)
            : undefined,
        product_id: !editingContract && productId > 0 ? productId : undefined,
        project_assignments: !editingContract && productId > 0 ? projectAssignments : undefined,
      })
      if (res.success) {
        backToList()
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

  const handleDelete = async (c: Contract) => {
    if (!confirm("آیا از حذف دائمی این قرارداد اطمینان دارید؟")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await deleteContract(c.id, c.delete_nonce)
      if (res.success) loadList()
      else setError(res.message ?? "خطا در حذف")
    } catch {
      setError("خطا در حذف")
    } finally {
      setSubmitting(false)
    }
  }

  const handleCancel = async () => {
    if (!editingContract || !confirm("آیا از لغو این قرارداد اطمینان دارید؟")) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await cancelContract(editingContract.id)
      if (res.success) loadContract(editingContract.id)
      else setError(res.message ?? "خطا در لغو")
    } catch {
      setError("خطا در لغو")
    } finally {
      setSubmitting(false)
    }
  }

  const handleAddProject = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!editingContract || !newProjectTitle.trim()) return
    setSubmitting(true)
    setError(null)
    try {
      const res = await addProjectToContract(editingContract.id, newProjectTitle.trim())
      if (res.success) {
        setNewProjectTitle("")
        loadContract(editingContract.id)
      } else {
        setError(res.message ?? "خطا در افزودن پروژه")
      }
    } catch {
      setError("خطا در افزودن پروژه")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="space-y-4" dir={isRtl ? "rtl" : "ltr"}>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">مدیریت قراردادها</h1>
        <div className="flex gap-2">
          <Button variant={tab === "list" ? "default" : "outline"} onClick={() => setTab("list")}>
            <List className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            لیست قراردادها
          </Button>
          <Button variant={tab === "new" ? "default" : "outline"} onClick={openNew}>
            <Plus className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
            قرارداد جدید
          </Button>
        </div>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {tab === "list" ? (
        <>
          <Card>
            <CardContent className="pt-6">
              <div className="flex flex-wrap gap-4">
                <div className="flex-1 min-w-[150px]">
                  <Label>جستجو</Label>
                  <Input
                    className="mt-1"
                    placeholder="شماره یا عنوان..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                  />
                </div>
                <div className="w-[180px]">
                  <Label>مشتری</Label>
                  <Select
                    value={customerFilter === "" ? SELECT_ALL_VALUE : customerFilter}
                    onValueChange={(v) => setCustomerFilter(v === SELECT_ALL_VALUE ? "" : v)}
                  >
                    <SelectTrigger className="mt-1">
                      <SelectValue placeholder="همه" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value={SELECT_ALL_VALUE}>همه مشتریان</SelectItem>
                      {customers.map((c) => (
                        <SelectItem key={c.id} value={String(c.id)}>
                          {c.display_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="w-[140px]">
                  <Label>وضعیت پرداخت</Label>
                  <Select
                    value={paymentStatusFilter === "" ? SELECT_ALL_VALUE : paymentStatusFilter}
                    onValueChange={(v) => setPaymentStatusFilter(v === SELECT_ALL_VALUE ? "" : v)}
                  >
                    <SelectTrigger className="mt-1">
                      <SelectValue placeholder="همه" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value={SELECT_ALL_VALUE}>همه</SelectItem>
                      <SelectItem value="paid">پرداخت شده</SelectItem>
                      <SelectItem value="pending">در انتظار</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="flex items-end">
                  <Button onClick={loadList}>فیلتر</Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-0">
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : contracts.length === 0 ? (
                <div className="py-12 text-center text-muted-foreground">
                  هیچ قراردادی یافت نشد.
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>شماره</TableHead>
                      <TableHead>مشتری</TableHead>
                      <TableHead>مبلغ کل</TableHead>
                      <TableHead>پرداخت شده</TableHead>
                      <TableHead>وضعیت</TableHead>
                      <TableHead>عملیات</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {contracts.map((c) => (
                      <TableRow key={c.id}>
                        <TableCell>
                          <span className="font-mono">#{c.contract_number}</span>
                          {c.is_cancelled && (
                            <Badge variant="destructive" className="ms-2">لغو شده</Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          <div>{c.customer_name}</div>
                          {c.customer_email && (
                            <div className="text-xs text-muted-foreground" dir="ltr">{c.customer_email}</div>
                          )}
                        </TableCell>
                        <TableCell dir="ltr">{formatNumber(c.total_amount)} تومان</TableCell>
                        <TableCell>
                          <div className="text-success" dir="ltr">{formatNumber(c.paid_amount)} تومان</div>
                          <div className="text-xs text-muted-foreground">{c.payment_percentage}%</div>
                        </TableCell>
                        <TableCell>
                          <Badge variant={c.status_class === "paid" ? "default" : "secondary"}>
                            {c.status_text}
                          </Badge>
                          {c.installment_count > 0 && (
                            <div className="text-xs text-muted-foreground mt-1">
                              {c.paid_count} پرداخت / {c.pending_count} در انتظار
                            </div>
                          )}
                        </TableCell>
                        <TableCell>
                          <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => openEdit(c)}>
                              <Pencil className="h-4 w-4" />
                            </Button>
                            {!c.is_cancelled && (
                              <Button
                                variant="outline"
                                size="sm"
                                className="text-destructive"
                                onClick={() => handleDelete(c)}
                                disabled={submitting}
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            )}
                          </div>
                          {c.start_date_jalali !== "-" && (
                            <div className="text-xs text-muted-foreground mt-1">{c.start_date_jalali}</div>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
          {totalPages > 1 && (
            <div className="flex justify-center gap-2">
              <Button variant="outline" size="sm" disabled={currentPage <= 1} onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}>
                قبلی
              </Button>
              <span className="flex items-center px-2 text-sm">
                صفحه {currentPage} از {totalPages}
              </span>
              <Button variant="outline" size="sm" disabled={currentPage >= totalPages} onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}>
                بعدی
              </Button>
            </div>
          )}
        </>
      ) : (
        <Card>
          <CardContent className="pt-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold">
                {editingContract ? "ویرایش قرارداد" : "قرارداد جدید"}
              </h3>
              <div className="flex gap-2">
                {editingContract && !editingContract.is_cancelled && (
                  <Button variant="outline" size="sm" onClick={handleCancel} disabled={submitting}>
                    <XCircle className="h-4 w-4" />
                    لغو قرارداد
                  </Button>
                )}
                <Button variant="outline" size="sm" onClick={backToList}>
                  بازگشت
                </Button>
              </div>
            </div>

            {editingContract?.is_cancelled && (
              <Alert variant="destructive" className="mb-4">
                <AlertDescription>این قرارداد لغو شده است.</AlertDescription>
              </Alert>
            )}

            {!editingContract && (
              <div className="flex items-center gap-2 mb-6 pb-4 border-b">
                {[1, 2, 3, 4].map((s) => (
                  <div key={s} className="flex items-center gap-1">
                    <div
                      className={cn(
                        "w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium",
                        wizardStep === s ? "bg-primary text-primary-foreground" : wizardStep > s ? "bg-primary/20 text-primary" : "bg-muted text-muted-foreground"
                      )}
                    >
                      {s}
                    </div>
                    {s < 4 && <ChevronRight className="h-4 w-4 text-muted-foreground" />}
                  </div>
                ))}
                <span className="text-sm text-muted-foreground mr-2">
                  {wizardStep === 1 && "اطلاعات پایه"}
                  {wizardStep === 2 && "اقساط"}
                  {wizardStep === 3 && "محصول"}
                  {wizardStep === 4 && "ارجاع پروژه‌ها"}
                </span>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {editingContract ? (
                <>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>مشتری *</Label>
                  <Select
                    value={form.customer_id ? String(form.customer_id) : ""}
                    onValueChange={(v) => setForm((f) => ({ ...f, customer_id: parseInt(v, 10) || 0 }))}
                    disabled
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="انتخاب مشتری" />
                    </SelectTrigger>
                    <SelectContent>
                      {customers.map((c) => (
                        <SelectItem key={c.id} value={String(c.id)}>
                          {c.display_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>تاریخ شروع *</Label>
                  <DatePicker
                    value={form.start_date}
                    onChange={(v) => setForm((f) => ({ ...f, start_date: v }))}
                    required
                  />
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label>عنوان قرارداد</Label>
                  <Input
                    value={form.contract_title}
                    onChange={(e) => setForm((f) => ({ ...f, contract_title: e.target.value }))}
                    placeholder="مثال: قرارداد پشتیبانی سالانه"
                  />
                </div>
                <div className="space-y-2">
                  <Label>مدت قرارداد</Label>
                  <Select value={form.duration} onValueChange={(v) => setForm((f) => ({ ...f, duration: v }))}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {(durations.length ? durations : [{ value: "1-month", label: "یک ماهه" }, { value: "3-months", label: "سه ماهه" }, { value: "6-months", label: "شش ماهه" }, { value: "12-months", label: "یک ساله" }]).map((d) => (
                        <SelectItem key={d.value} value={d.value}>{d.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>مدل اشتراک</Label>
                  <Select value={form.subscription_model} onValueChange={(v) => setForm((f) => ({ ...f, subscription_model: v }))}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {(models.length ? models : [{ value: "onetime", label: "یکبار پرداخت" }, { value: "subscription", label: "اشتراکی" }]).map((m) => (
                        <SelectItem key={m.value} value={m.value}>{m.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">اقساط</h4>
                <div className="flex flex-wrap gap-4 items-end p-4 bg-muted/50 rounded-lg">
                  <div className="space-y-2">
                    <Label>مبلغ کل (تومان)</Label>
                    <Input
                      placeholder="30,000,000"
                      value={form.total_amount}
                      onChange={(e) => setForm((f) => ({ ...f, total_amount: e.target.value }))}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>تعداد اقساط</Label>
                    <Input
                      type="number"
                      min={1}
                      value={form.total_installments}
                      onChange={(e) => setForm((f) => ({ ...f, total_installments: parseInt(e.target.value, 10) || 1 }))}
                    />
                  </div>
                  <Button type="button" variant="secondary" onClick={calculateInstallments}>
                    محاسبه
                  </Button>
                </div>
                <div className="flex justify-between items-center">
                  <Label>لیست اقساط</Label>
                  <Button type="button" variant="outline" size="sm" onClick={addInstallmentRow}>
                    افزودن قسط دستی
                  </Button>
                </div>
                <div className="space-y-2">
                  {form.installments.map((row, idx) => (
                    <div key={idx} className="flex gap-2 items-center">
                      <span className="w-8 text-muted-foreground">#{idx + 1}</span>
                      <Input
                        placeholder="مبلغ"
                        value={row.amount}
                        onChange={(e) => updateInstallment(idx, "amount", e.target.value)}
                        className="flex-1"
                      />
                      <DatePicker
                        value={row.due_date}
                        onChange={(v) => updateInstallment(idx, "due_date", v)}
                        className="flex-1"
                      />
                      <Select value={row.status} onValueChange={(v) => updateInstallment(idx, "status", v)}>
                        <SelectTrigger className="w-[130px]">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="pending">در انتظار</SelectItem>
                          <SelectItem value="paid">پرداخت شده</SelectItem>
                          <SelectItem value="cancelled">لغو شده</SelectItem>
                        </SelectContent>
                      </Select>
                      <Button type="button" variant="ghost" size="icon" onClick={() => removeInstallmentRow(idx)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex gap-4">
                <Button type="submit" disabled={submitting || (editingContract?.is_cancelled ?? false)}>
                  {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                  ذخیره تغییرات
                </Button>
                <Button type="button" variant="outline" onClick={backToList}>
                  انصراف
                </Button>
              </div>
                </>
              ) : (
                <>
              {(wizardStep === 1) && (
                <div className="grid gap-4 sm:grid-cols-2">
                  {leadForContract && (
                    <div className="sm:col-span-2 rounded-lg border p-4 bg-muted/30 space-y-2">
                      <p className="text-sm font-medium">سرنخ #{leadForContract.lead_id}: {leadForContract.first_name} {leadForContract.last_name} — {leadForContract.mobile}</p>
                      {leadForContract.existing_customer_id > 0 ? (
                        <p className="text-sm text-muted-foreground">مشتری با این موبایل/ایمیل از قبل وجود دارد و در لیست انتخاب شده است.</p>
                      ) : (
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={creatingCustomerFromLead}
                          onClick={async () => {
                            setCreatingCustomerFromLead(true)
                            setError(null)
                            const res = await createCustomerFromLead(leadForContract.lead_id)
                            setCreatingCustomerFromLead(false)
                            if (res.success && res.data?.customer_id) {
                              setForm((f) => ({ ...f, customer_id: res.data!.customer_id }))
                              getContracts({}).then((r) => {
                                if (r.success && r.data) setCustomers(r.data.customers ?? [])
                              })
                            } else {
                              setError(res.message ?? "خطا در ایجاد مشتری")
                            }
                          }}
                        >
                          {creatingCustomerFromLead ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                          ایجاد مشتری از سرنخ
                        </Button>
                      )}
                    </div>
                  )}
                  <div className="space-y-2">
                    <Label>مشتری *</Label>
                    <Select value={form.customer_id ? String(form.customer_id) : ""} onValueChange={(v) => setForm((f) => ({ ...f, customer_id: parseInt(v, 10) || 0 }))}>
                      <SelectTrigger><SelectValue placeholder="انتخاب مشتری" /></SelectTrigger>
                      <SelectContent>
                        {customers.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.display_name}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>تاریخ شروع *</Label>
                    <DatePicker value={form.start_date} onChange={(v) => setForm((f) => ({ ...f, start_date: v }))} required />
                  </div>
                  <div className="space-y-2 sm:col-span-2">
                    <Label>عنوان قرارداد</Label>
                    <Input value={form.contract_title} onChange={(e) => setForm((f) => ({ ...f, contract_title: e.target.value }))} placeholder="مثال: قرارداد پشتیبانی سالانه" />
                  </div>
                  <div className="space-y-2">
                    <Label>مدت قرارداد</Label>
                    <Select value={form.duration} onValueChange={(v) => setForm((f) => ({ ...f, duration: v }))}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        {(durations.length ? durations : [{ value: "1-month", label: "یک ماهه" }, { value: "3-months", label: "سه ماهه" }, { value: "6-months", label: "شش ماهه" }, { value: "12-months", label: "یک ساله" }]).map((d) => <SelectItem key={d.value} value={d.value}>{d.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>مدل اشتراک</Label>
                    <Select value={form.subscription_model} onValueChange={(v) => setForm((f) => ({ ...f, subscription_model: v }))}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        {(models.length ? models : [{ value: "onetime", label: "یکبار پرداخت" }, { value: "subscription", label: "اشتراکی" }]).map((m) => <SelectItem key={m.value} value={m.value}>{m.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              )}
              {(wizardStep === 2) && (
                <div className="space-y-4">
                  <h4 className="font-medium">اقساط</h4>
                  <div className="flex flex-wrap gap-4 items-end p-4 bg-muted/50 rounded-lg">
                    <div className="space-y-2">
                      <Label>مبلغ کل (تومان)</Label>
                      <Input placeholder="30,000,000" value={form.total_amount} onChange={(e) => setForm((f) => ({ ...f, total_amount: e.target.value }))} />
                    </div>
                    <div className="space-y-2">
                      <Label>تعداد اقساط</Label>
                      <Input type="number" min={1} value={form.total_installments} onChange={(e) => setForm((f) => ({ ...f, total_installments: parseInt(e.target.value, 10) || 1 }))} />
                    </div>
                    <Button type="button" variant="secondary" onClick={calculateInstallments}>محاسبه</Button>
                  </div>
                  <div className="flex justify-between items-center">
                    <Label>لیست اقساط</Label>
                    <Button type="button" variant="outline" size="sm" onClick={addInstallmentRow}>افزودن قسط دستی</Button>
                  </div>
                  <div className="space-y-2">
                    {form.installments.map((row, idx) => (
                      <div key={idx} className="flex gap-2 items-center">
                        <span className="w-8 text-muted-foreground">#{idx + 1}</span>
                        <Input placeholder="مبلغ" value={row.amount} onChange={(e) => updateInstallment(idx, "amount", e.target.value)} className="flex-1" />
                        <DatePicker value={row.due_date} onChange={(v) => updateInstallment(idx, "due_date", v)} className="flex-1" />
                        <Select value={row.status} onValueChange={(v) => updateInstallment(idx, "status", v)}>
                          <SelectTrigger className="w-[130px]"><SelectValue /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="pending">در انتظار</SelectItem>
                            <SelectItem value="paid">پرداخت شده</SelectItem>
                            <SelectItem value="cancelled">لغو شده</SelectItem>
                          </SelectContent>
                        </Select>
                        <Button type="button" variant="ghost" size="icon" onClick={() => removeInstallmentRow(idx)}><Trash2 className="h-4 w-4 text-destructive" /></Button>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              {(wizardStep === 3) && (
                <div className="space-y-4">
                  <h4 className="font-medium flex items-center gap-2"><Package className="h-5 w-5" /> انتخاب محصول ووکامرس</h4>
                  {wcActive ? (
                    <>
                      <Select value={productId > 0 ? String(productId) : "_none"} onValueChange={(v) => setProductId(v === "_none" ? 0 : parseInt(v, 10))}>
                        <SelectTrigger className="max-w-md">
                          <SelectValue placeholder="انتخاب محصول (اختیاری)" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="_none">بدون محصول</SelectItem>
                          {wcProducts.map((p) => (
                            <SelectItem key={p.id} value={String(p.id)}>{p.name} ({p.type}) — {p.price}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {productId === 0 && (
                        <div className="rounded-lg border p-4 space-y-4 bg-muted/30">
                          <div className="flex items-center gap-2">
                            <input type="checkbox" id="create-project-template" checked={createProjectWithTemplate} onChange={(e) => { setCreateProjectWithTemplate(e.target.checked); if (!e.target.checked) setProjectTemplateId("") }} className="h-4 w-4 rounded border-input" />
                            <Label htmlFor="create-project-template" className="cursor-pointer font-medium">ایجاد پروژه با قالب</Label>
                          </div>
                          {createProjectWithTemplate && (
                            <Select value={projectTemplateId} onValueChange={setProjectTemplateId}>
                              <SelectTrigger className="max-w-md"><SelectValue placeholder="انتخاب قالب..." /></SelectTrigger>
                              <SelectContent>
                                {projectTemplates.map((t) => <SelectItem key={t.id} value={String(t.id)}>{t.title}</SelectItem>)}
                                {projectTemplates.length === 0 && <SelectItem value="__none__" disabled>قالبی یافت نشد</SelectItem>}
                              </SelectContent>
                            </Select>
                          )}
                        </div>
                      )}
                    </>
                  ) : (
                    <p className="text-muted-foreground">ووکامرس فعال نیست. از قالب پروژه استفاده کنید.</p>
                  )}
                </div>
              )}
              {(wizardStep === 4) && (
                <div className="space-y-4">
                  <h4 className="font-medium flex items-center gap-2"><UserPlus className="h-5 w-5" /> ارجاع پروژه‌ها</h4>
                  {projectTitlesPreview.length > 0 ? (
                    <div className="space-y-3">
                      {projectTitlesPreview.map((title, idx) => (
                        <div key={idx} className="flex items-center gap-4">
                          <span className="font-medium min-w-[180px]">{title}</span>
                          <Select value={projectAssignments[idx] > 0 ? String(projectAssignments[idx]) : "_none"} onValueChange={(v) => setProjectAssignments((prev) => { const n = [...prev]; n[idx] = v === "_none" ? 0 : parseInt(v, 10); return n })}>
                            <SelectTrigger className="max-w-[220px]"><SelectValue placeholder="ارجاع به..." /></SelectTrigger>
                            <SelectContent>
                              <SelectItem value="_none">—</SelectItem>
                              {projectAssignees.map((u) => <SelectItem key={u.id} value={String(u.id)}>{u.display_name}</SelectItem>)}
                            </SelectContent>
                          </Select>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-muted-foreground">محصولی انتخاب نشده یا پروژه‌ای از آن ایجاد نمی‌شود.</p>
                  )}
                </div>
              )}
              <div className="flex gap-4">
                {wizardStep > 1 && (
                  <Button type="button" variant="outline" onClick={() => setWizardStep((s) => s - 1)}>
                    <ChevronRight className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                    قبلی
                  </Button>
                )}
                {wizardStep < 4 ? (
                  <Button
                    type="button"
                    onClick={() => setWizardStep((s) => s + 1)}
                    disabled={
                      (wizardStep === 1 && (!form.customer_id || !form.start_date)) ||
                      (wizardStep === 2 && (form.installments.length === 0 || form.installments.some((i) => !i.amount || !i.due_date)))
                    }
                  >
                    بعدی
                    <ChevronLeft className={cn("h-4 w-4 shrink-0", isRtl ? "ms-2" : "me-2")} />
                  </Button>
                ) : (
                  <Button type="submit" disabled={submitting}>
                    {submitting && <Loader2 className={cn("h-4 w-4 animate-spin shrink-0", isRtl ? "ms-2" : "me-2")} />}
                    ایجاد قرارداد
                  </Button>
                )}
                <Button type="button" variant="outline" onClick={backToList}>انصراف</Button>
              </div>
                </>
              )}
            </form>

            {editingContract && (
              <div className="mt-8 pt-8 border-t">
                <h4 className="font-medium mb-4">پروژه‌های مرتبط</h4>
                {relatedProjects.length > 0 ? (
                  <ul className="list-disc list-inside space-y-1 mb-4">
                    {relatedProjects.map((p) => (
                      <li key={p.id}>
                        <Link
                          to={`/projects?action=edit&project_id=${p.id}`}
                          className="text-primary hover:underline"
                        >
                          {p.title}
                        </Link>
                      </li>
                    ))}
                  </ul>
                ) : (
                  <p className="text-muted-foreground mb-4">هیچ پروژه‌ای به این قرارداد متصل نیست.</p>
                )}
                <form onSubmit={handleAddProject} className="flex gap-2">
                  <Input
                    placeholder="عنوان پروژه جدید"
                    value={newProjectTitle}
                    onChange={(e) => setNewProjectTitle(e.target.value)}
                    className="flex-1"
                  />
                  <Button type="submit" disabled={submitting || !newProjectTitle.trim()}>
                    افزودن پروژه
                  </Button>
                </form>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
