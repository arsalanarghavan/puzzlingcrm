import { Component, type ErrorInfo, type ReactNode } from "react"
import { Button } from "@/components/ui/button"
import { AlertTriangle } from "lucide-react"

interface Props {
  children: ReactNode
}

interface State {
  hasError: boolean
  error: Error | null
}

export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error("ErrorBoundary caught:", error, errorInfo)
  }

  handleRetry = () => {
    this.setState({ hasError: false, error: null })
  }

  handleRefresh = () => {
    window.location.reload()
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="flex min-h-[280px] flex-col items-center justify-center gap-4 rounded-lg border border-destructive/30 bg-destructive/5 p-8 text-center">
          <AlertTriangle className="h-12 w-12 text-destructive" />
          <div>
            <h3 className="font-semibold text-foreground">خطا در بارگذاری</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              {this.state.error?.message ?? "یک خطای غیرمنتظره رخ داد."}
            </p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={this.handleRetry}>
              تلاش مجدد
            </Button>
            <Button onClick={this.handleRefresh}>بارگذاری مجدد صفحه</Button>
          </div>
        </div>
      )
    }
    return this.props.children
  }
}
