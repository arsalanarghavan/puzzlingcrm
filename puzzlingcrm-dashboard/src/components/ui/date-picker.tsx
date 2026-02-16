import * as React from "react"
import DatePickerLib from "react-multi-date-picker"
import DateObject from "react-date-object"
import gregorian from "react-date-object/calendars/gregorian"
import persian from "react-date-object/calendars/persian"
import gregorian_en from "react-date-object/locales/gregorian_en"
import persian_fa from "react-date-object/locales/persian_fa"
import { getCurrentLanguage } from "@/lib/language"
import { cn } from "@/lib/utils"

/** ISO date string YYYY-MM-DD (Gregorian) for API. */
export interface DatePickerProps {
  value: string
  onChange: (isoDate: string) => void
  disabled?: boolean
  required?: boolean
  placeholder?: string
  className?: string
  inputClass?: string
  id?: string
}

function getCalendarAndLocale(): { calendar: typeof gregorian | typeof persian; locale: typeof gregorian_en | typeof persian_fa } {
  const lang = getCurrentLanguage()
  if (lang === "fa") {
    return { calendar: persian, locale: persian_fa }
  }
  return { calendar: gregorian, locale: gregorian_en }
}

export const DatePicker = React.forwardRef<HTMLDivElement, DatePickerProps>(
  ({ value, onChange, disabled, required, placeholder, className, inputClass, id }, ref) => {
    const { calendar, locale } = React.useMemo(() => getCalendarAndLocale(), [])

    const pickerValue = React.useMemo(() => {
      if (!value || value.trim() === "") return undefined
      try {
        return new DateObject(value)
      } catch {
        return undefined
      }
    }, [value])

    const handleChange = React.useCallback(
      (d: InstanceType<typeof DateObject> | null) => {
        if (!d) {
          onChange("")
          return
        }
        const g = d.convert(gregorian)
        onChange(g.format("YYYY-MM-DD"))
      },
      [onChange]
    )

    const inputClassName = cn(
      "flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm",
      inputClass
    )

    return (
      <div ref={ref} className={cn("w-full", className)}>
        <DatePickerLib
          value={pickerValue}
          onChange={handleChange}
          calendar={calendar}
          locale={locale}
          format="YYYY/MM/DD"
          disabled={disabled}
          required={required}
          placeholder={placeholder ?? (getCurrentLanguage() === "fa" ? "تاریخ" : "Date")}
          inputClass={inputClassName}
          containerClassName="w-full"
          id={id}
        />
      </div>
    )
  }
)
DatePicker.displayName = "DatePicker"
