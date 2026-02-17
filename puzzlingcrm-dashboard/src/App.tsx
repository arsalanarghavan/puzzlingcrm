import { useEffect } from "react"
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom"
import { ThemeProvider } from "@/contexts/theme-context"
import { Layout } from "@/components/layout/layout"
import { DashboardPage } from "@/pages/dashboard-page"
import { LeadsPage } from "@/pages/leads-page"
import { ProfilePage } from "@/pages/profile-page"
import { LicensesPage } from "@/pages/licenses-page"
import { ConsultationsPage } from "@/pages/consultations-page"
import { LogsPage } from "@/pages/logs-page"
import { CustomersPage } from "@/pages/customers-page"
import { ContractsPage } from "@/pages/contracts-page"
import { InvoicesPage } from "@/pages/invoices-page"
import { TicketsPage } from "@/pages/tickets-page"
import { ProjectsPage } from "@/pages/projects-page"
import { TasksPage } from "@/pages/tasks-page"
import { AppointmentsPage } from "@/pages/appointments-page"
import { StaffPage } from "@/pages/staff-page"
import { ReportsPage } from "@/pages/reports-page"
import { VisitorStatisticsPage } from "@/pages/visitor-statistics-page"
import { SettingsPage } from "@/pages/settings-page"
import { ServicesPage } from "@/pages/services-page"
import { CampaignsPage } from "@/pages/campaigns-page"
import { AccountingDashboardPage } from "@/pages/accounting/AccountingDashboardPage"
import { ChartOfAccountsPage } from "@/pages/accounting/ChartOfAccountsPage"
import { JournalsPage } from "@/pages/accounting/JournalsPage"
import { LedgerPage } from "@/pages/accounting/LedgerPage"
import { AccountingReportsPage } from "@/pages/accounting/AccountingReportsPage"
import { FiscalYearPage } from "@/pages/accounting/FiscalYearPage"
import { AccountingSettingsPage } from "@/pages/accounting/AccountingSettingsPage"
import { PersonsPage } from "@/pages/accounting/PersonsPage"
import { ProductsPage } from "@/pages/accounting/ProductsPage"
import { InvoicesPage } from "@/pages/accounting/InvoicesPage"
import { CashAccountsPage } from "@/pages/accounting/CashAccountsPage"
import { ReceiptsPage } from "@/pages/accounting/ReceiptsPage"
import { ChecksPage } from "@/pages/accounting/ChecksPage"
import { getConfigOrNull } from "@/api/client"

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Layout />}>
        <Route index element={<DashboardPage />} />
        <Route path="leads" element={<LeadsPage />} />
        <Route path="profile" element={<ProfilePage />} />
        <Route path="licenses" element={<LicensesPage />} />
        <Route path="consultations" element={<ConsultationsPage />} />
        <Route path="logs" element={<LogsPage />} />
        <Route path="customers" element={<CustomersPage />} />
        <Route path="contracts/*" element={<ContractsPage />} />
        <Route path="invoices/*" element={<InvoicesPage />} />
        <Route path="tickets/*" element={<TicketsPage />} />
        <Route path="projects/*" element={<ProjectsPage />} />
        <Route path="tasks/*" element={<TasksPage />} />
        <Route path="appointments/*" element={<AppointmentsPage />} />
        <Route path="staff/*" element={<StaffPage />} />
        <Route path="reports/*" element={<ReportsPage />} />
        <Route path="visitor-statistics" element={<VisitorStatisticsPage />} />
        <Route path="services/*" element={<ServicesPage />} />
        <Route path="campaigns/*" element={<CampaignsPage />} />
        <Route path="settings/*" element={<SettingsPage />} />
        <Route path="accounting" element={<AccountingDashboardPage />} />
        <Route path="accounting/chart" element={<ChartOfAccountsPage />} />
        <Route path="accounting/journals" element={<JournalsPage />} />
        <Route path="accounting/ledger" element={<LedgerPage />} />
        <Route path="accounting/reports" element={<AccountingReportsPage />} />
        <Route path="accounting/fiscal-year" element={<FiscalYearPage />} />
        <Route path="accounting/settings" element={<AccountingSettingsPage />} />
        <Route path="accounting/persons" element={<PersonsPage />} />
        <Route path="accounting/products" element={<ProductsPage />} />
        <Route path="accounting/invoices" element={<InvoicesPage />} />
        <Route path="accounting/cash-accounts" element={<CashAccountsPage />} />
        <Route path="accounting/receipts" element={<ReceiptsPage />} />
        <Route path="accounting/checks" element={<ChecksPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  )
}

function App() {
  useEffect(() => {
    const c = getConfigOrNull()
    if (c) {
      const dir = c.isRtl ? "rtl" : "ltr"
      const lang = c.locale ?? (c.isRtl ? "fa" : "en")
      document.documentElement.setAttribute("dir", dir)
      document.documentElement.setAttribute("lang", lang)
    }
  }, [])

  const basename =
    typeof window !== "undefined" && window.puzzlingcrm?.dashboardBasePath
      ? window.puzzlingcrm.dashboardBasePath
      : "/dashboard"

  return (
    <ThemeProvider>
      <BrowserRouter basename={basename}>
        <AppRoutes />
      </BrowserRouter>
    </ThemeProvider>
  )
}

export default App
