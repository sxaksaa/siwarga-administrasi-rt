export function localDateInput(date = new Date()) {
  const localTime = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
  return localTime.toISOString().slice(0, 10)
}

export function localMonthInput(date = new Date()) {
  return localDateInput(date).slice(0, 7)
}
