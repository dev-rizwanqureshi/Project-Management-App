export const titleCase = (value: string) =>
    value
        .trim()
        .split(/\s+/)
        .map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`)
        .join(' ');

export const truncate = (value: string, length = 80) =>
    value.length > length ? `${value.slice(0, length).trim()}...` : value;
