import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

interface PlaceholderPageProps {
  title: string
}

export function PlaceholderPage({ title }: PlaceholderPageProps) {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">{title}</h1>
      <Card>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            این بخش در نسخه فعلی در حال تکمیل است. به‌زودی با لیست، فرم و امکانات کامل در همین پنل در دسترس خواهد بود.
          </p>
        </CardContent>
      </Card>
    </div>
  )
}
