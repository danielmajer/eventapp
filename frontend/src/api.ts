export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export interface Event {
  id: number;
  title: string;
  occurs_at: string;
  description?: string | null;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  mfa_enabled?: boolean;
}

export class ApiError extends Error {
  status?: number;

  constructor(
    message: string,
    status?: number,
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

/**
 * Extracts error message from response text
 * Handles JSON errors, HTML errors, and plain text
 */
function extractErrorMessage(text: string, status: number): string {
  // Check if response is HTML (Laravel error page, etc.)
  if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
    return 'An error occurred. Please try again later.';
  }

  // Try to parse as JSON
  try {
    const json = JSON.parse(text);
    
    // Laravel validation errors
    if (json.errors && typeof json.errors === 'object') {
      const firstError = Object.values(json.errors)[0];
      if (Array.isArray(firstError) && firstError.length > 0) {
        return String(firstError[0]);
      }
    }
    
    // Standard error message
    if (json.message && typeof json.message === 'string') {
      return json.message;
    }
    
    // Fallback to first string value found
    for (const value of Object.values(json)) {
      if (typeof value === 'string' && value.trim()) {
        return value;
      }
    }
  } catch {
    // Not JSON, continue to plain text handling
  }

  // Plain text error - use it if it's reasonable length
  const trimmed = text.trim();
  if (trimmed && trimmed.length < 500) {
    return trimmed;
  }

  // Default error messages based on status code
  switch (status) {
    case 401:
      return 'Authentication required. Please log in.';
    case 403:
      return 'You do not have permission to perform this action.';
    case 404:
      return 'The requested resource was not found.';
    case 422:
      return 'Validation failed. Please check your input.';
    case 429:
      return 'Too many requests. Please try again later.';
    case 500:
    case 502:
    case 503:
      return 'Server error. Please try again later.';
    default:
      return 'An error occurred. Please try again.';
  }
}

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {},
  token?: string | null,
): Promise<T> {
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  };

  if (token) {
    (headers as Record<string, string>).Authorization = `Bearer ${token}`;
  }

  const res = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  if (!res.ok) {
    const text = await res.text();
    const errorMessage = extractErrorMessage(text, res.status);
    const error = new ApiError(errorMessage, res.status);
    throw error;
  }

  return res.json();
}


