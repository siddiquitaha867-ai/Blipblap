const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
  hour: 'numeric',
  minute: '2-digit',
});

const dateFormatter = new Intl.DateTimeFormat(undefined, {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
});

const relativeFormatter = new Intl.RelativeTimeFormat(undefined, {
  numeric: 'auto',
});

const units = [
  ['year', 31536000000],
  ['month', 2592000000],
  ['week', 604800000],
  ['day', 86400000],
  ['hour', 3600000],
  ['minute', 60000],
];

const normalizeDateValue = (value) => {
  if (!value) {
    return null;
  }

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value;
  }

  if (typeof value !== 'string' && typeof value !== 'number') {
    return null;
  }

  const raw = String(value).trim();

  if (!raw) {
    return null;
  }

  const isoLike = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/.test(raw)
    ? raw.replace(' ', 'T')
    : raw;
  const parsed = new Date(isoLike);

  return Number.isNaN(parsed.getTime()) ? null : parsed;
};

export const formatDateTime = (value, fallback = '-') => {
  const date = normalizeDateValue(value);

  return date ? dateTimeFormatter.format(date) : fallback;
};

export const formatDate = (value, fallback = '-') => {
  const date = normalizeDateValue(value);

  return date ? dateFormatter.format(date) : fallback;
};

export const formatRelativeTime = (value, fallback = '-') => {
  const date = normalizeDateValue(value);

  if (!date) {
    return fallback;
  }

  const diff = date.getTime() - Date.now();
  const unit = units.find(([, milliseconds]) => Math.abs(diff) >= milliseconds);

  if (!unit) {
    return 'just now';
  }

  return relativeFormatter.format(Math.round(diff / unit[1]), unit[0]);
};
