export const formatDate = (
    value: string | Date,
    locale = 'en',
    options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    },
) => new Intl.DateTimeFormat(locale, options).format(new Date(value));
