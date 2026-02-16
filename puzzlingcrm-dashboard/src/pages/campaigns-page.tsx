import { Card, CardContent } from "@/components/ui/card"
import { getConfigOrNull } from "@/api/client"
import { cn } from "@/lib/utils"
import { Megaphone } from "lucide-react"

export function CampaignsPage() {
  const config = getConfigOrNull()
  const isRtl = config?.isRtl === true

  return (
    <div className={cn("space-y-4", isRtl && "text-right")} dir={isRtl ? "rtl" : "ltr"}>
      <Card>
        <CardContent className="flex flex-col items-center justify-center py-16">
          <Megaphone className="h-16 w-16 text-muted-foreground mb-4" />
          <h2 className="text-xl font-semibold mb-2">کمپین‌ها</h2>
          <p className="text-muted-foreground">به زودی...</p>
        </CardContent>
      </Card>
    </div>
  )
}
