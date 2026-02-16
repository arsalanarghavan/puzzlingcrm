import { useCallback, useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog"
import { getConfigOrNull } from "@/api/client"
import {
  listSubscriptions,
  listProducts,
  convertSubscriptionToContract,
  updateProductTaskTemplate,
  type Subscription,
  type Product,
  type TaskTemplate,
} from "@/api/services"
import { cn } from "@/lib/utils"
import { Loader2, FileText, ShoppingBag, RefreshCw, Pencil } from "lucide-react"

const SERVICE_TYPES = [
  { value: "onetime", label: "یکبار (مثل سایت)" },
  { value: "daily", label: "روزانه (مثل اینستاگرام)" },
  { value: "weekly", label: "هفتگی" },
  { value: "monthly", label: "ماهانه" },
]

export function ServicesPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true
  const [activeTab, setActiveTab] = useState("subscriptions")
  const [subscriptions, setSubscriptions] = useState<Subscription[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [taskTemplates, setTaskTemplates] = useState<TaskTemplate[]>([])
  const [wcSubscriptionsActive, setWcSubscriptionsActive] = useState(false)
  const [wcActive, setWcActive] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [convertingId, setConvertingId] = useState<number | null>(null)
  const [editProduct, setEditProduct] = useState<Product | null>(null)
  const [editForm, setEditForm] = useState({ task_template_id: null as number | null, service_task_type: "onetime" })
  const [submitting, setSubmitting] = useState(false)

  const loadSubscriptions = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await listSubscriptions()
      if (res.success && res.data) {
        setSubscriptions(res.data.subscriptions ?? [])
        setWcSubscriptionsActive(res.data.wc_subscriptions_active ?? false)
      } else {
        setError(res.message ?? "خطا در بارگذاری اشتراک‌ها")
      }
    } catch {
      setError("خطا در بارگذاری اشتراک‌ها")
    } finally {
      setLoading(false)
    }
  }, [])

  const loadProducts = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await listProducts()
      if (res.success && res.data) {
        setProducts(res.data.products ?? [])
        setTaskTemplates(res.data.task_templates ?? [])
        setWcActive(res.data.wc_active ?? false)
      } else {
        setError(res.message ?? "خطا در بارگذاری محصولات")
      }
    } catch {
      setError("خطا در بارگذاری محصولات")
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (activeTab === "subscriptions") {
      loadSubscriptions()
    } else {
      loadProducts()
    }
  }, [activeTab, loadSubscriptions, loadProducts])

  const handleConvert = async (sub: Subscription) => {
    if (sub.already_converted) return
    setConvertingId(sub.id)
    try {
      const res = await convertSubscriptionToContract(sub.id)
      if (res.success && res.data?.contract_id) {
        loadSubscriptions()
      } else {
        setError(res.message ?? "خطا در تبدیل اشتراک")
      }
    } catch {
      setError("خطا در تبدیل اشتراک")
    } finally {
      setConvertingId(null)
    }
  }

  const openEditProduct = (p: Product) => {
    setEditProduct(p)
    setEditForm({
      task_template_id: p.task_template_id,
      service_task_type: p.service_task_type || "onetime",
    })
  }

  const handleSaveProductTaskTemplate = async () => {
    if (!editProduct) return
    setSubmitting(true)
    try {
      const res = await updateProductTaskTemplate({
        product_id: editProduct.id,
        task_template_id: editForm.task_template_id,
        service_task_type: editForm.service_task_type,
      })
      if (res.success) {
        setEditProduct(null)
        loadProducts()
      } else {
        setError(res.message ?? "خطا در ذخیره")
      }
    } catch {
      setError("خطا در ذخیره")
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className={cn("space-y-4", isRtl && "text-right")} dir={isRtl ? "rtl" : "ltr"}>
      <Card>
        <CardContent className="pt-6">
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList>
              <TabsTrigger value="subscriptions" className="gap-2">
                <RefreshCw className="h-4 w-4" />
                اشتراک‌های ووکامرس
              </TabsTrigger>
              <TabsTrigger value="products" className="gap-2">
                <ShoppingBag className="h-4 w-4" />
                محصولات و خدمات
              </TabsTrigger>
            </TabsList>

            <TabsContent value="subscriptions" className="mt-4">
              {!wcSubscriptionsActive && (
                <Alert className="mb-4">
                  <AlertDescription>
                    افزونه WooCommerce Subscriptions فعال نیست. برای مدیریت اشتراک‌ها، افزونه را نصب و فعال کنید.
                  </AlertDescription>
                </Alert>
              )}
              {error && (
                <Alert variant="destructive" className="mb-4">
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>اشتراک</TableHead>
                      <TableHead>مشتری</TableHead>
                      <TableHead>وضعیت</TableHead>
                      <TableHead>مبلغ</TableHead>
                      <TableHead>تاریخ شروع</TableHead>
                      <TableHead>پرداخت بعدی</TableHead>
                      <TableHead className="text-left">عملیات</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {subscriptions.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                          {wcSubscriptionsActive ? "هیچ اشتراکی یافت نشد." : "افزونه اشتراک‌ها غیرفعال است."}
                        </TableCell>
                      </TableRow>
                    ) : (
                      subscriptions.map((sub) => (
                        <TableRow key={sub.id}>
                          <TableCell>#{sub.id}</TableCell>
                          <TableCell>
                            {sub.customer_id ? (
                              <Link to={`/customers?edit=${sub.customer_id}`} className="text-primary hover:underline">
                                {sub.customer_name}
                              </Link>
                            ) : (
                              sub.customer_name
                            )}
                          </TableCell>
                          <TableCell>
                            <Badge variant={sub.status === "active" ? "default" : "secondary"}>
                              {sub.status_name}
                            </Badge>
                          </TableCell>
                          <TableCell>{sub.total_formatted}</TableCell>
                          <TableCell>{sub.start_date || "-"}</TableCell>
                          <TableCell>{sub.next_payment || "-"}</TableCell>
                          <TableCell>
                            {sub.already_converted ? (
                              <Link to="/contracts">
                                <Button variant="outline" size="sm" className="gap-1">
                                  <FileText className="h-4 w-4" />
                                  مشاهده قراردادها
                                </Button>
                              </Link>
                            ) : (
                              <Button
                                size="sm"
                                onClick={() => handleConvert(sub)}
                                disabled={convertingId === sub.id}
                                className="gap-1"
                              >
                                {convertingId === sub.id ? (
                                  <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                  <FileText className="h-4 w-4" />
                                )}
                                تبدیل به قرارداد
                              </Button>
                            )}
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              )}
            </TabsContent>

            <TabsContent value="products" className="mt-4">
              {!wcActive && (
                <Alert className="mb-4">
                  <AlertDescription>افزونه ووکامرس فعال نیست.</AlertDescription>
                </Alert>
              )}
              {error && (
                <Alert variant="destructive" className="mb-4">
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}
              {loading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>محصول</TableHead>
                      <TableHead>نوع</TableHead>
                      <TableHead>قیمت</TableHead>
                      <TableHead>قالب تسک</TableHead>
                      <TableHead>نوع خدمت</TableHead>
                      <TableHead className="text-left">عملیات</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {products.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                          {wcActive ? "هیچ محصولی یافت نشد." : "افزونه ووکامرس غیرفعال است."}
                        </TableCell>
                      </TableRow>
                    ) : (
                      products.map((p) => (
                        <TableRow key={p.id}>
                          <TableCell>{p.name}</TableCell>
                          <TableCell>
                            <Badge variant="outline">{p.type}</Badge>
                          </TableCell>
                          <TableCell>{p.price || "-"}</TableCell>
                          <TableCell>{p.task_template_title || "-"}</TableCell>
                          <TableCell>
                            {SERVICE_TYPES.find((s) => s.value === p.service_task_type)?.label ?? p.service_task_type}
                          </TableCell>
                          <TableCell>
                            <Button variant="outline" size="sm" onClick={() => openEditProduct(p)} className="gap-1">
                              <Pencil className="h-4 w-4" />
                              ویرایش
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              )}
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <Dialog open={!!editProduct} onOpenChange={(open) => !open && setEditProduct(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>اتصال قالب تسک به محصول</DialogTitle>
          </DialogHeader>
          {editProduct && (
            <div className="space-y-4">
              <p className="text-sm text-muted-foreground">{editProduct.name}</p>
              <div className="space-y-2">
                <label className="text-sm font-medium">قالب تسک</label>
                <Select
                  value={editForm.task_template_id ? String(editForm.task_template_id) : "0"}
                  onValueChange={(v) => setEditForm((f) => ({ ...f, task_template_id: v === "0" ? null : parseInt(v, 10) }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="انتخاب کنید" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="0">هیچکدام</SelectItem>
                    {taskTemplates.map((t) => (
                      <SelectItem key={t.id} value={String(t.id)}>
                        {t.title} {t.is_recurring && `(${t.recurring_type})`}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <label className="text-sm font-medium">نوع خدمت (تکرار تسک)</label>
                <Select
                  value={editForm.service_task_type}
                  onValueChange={(v) => setEditForm((f) => ({ ...f, service_task_type: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {SERVICE_TYPES.map((s) => (
                      <SelectItem key={s.value} value={s.value}>
                        {s.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditProduct(null)}>
              انصراف
            </Button>
            <Button onClick={handleSaveProductTaskTemplate} disabled={submitting}>
              {submitting && <Loader2 className="h-4 w-4 animate-spin ml-2" />}
              ذخیره
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
