import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { authFetch } from '../app/api/authFetch.js'
import { normalizeSessionUser, readJsonSafe } from '../app/api/slaminApi.js'

const AuthContext = createContext(null)

const TOKEN_STORAGE_KEY = 'token'

function getStoredToken() {
  return localStorage.getItem(TOKEN_STORAGE_KEY)
}

function setStoredToken(token) {
  localStorage.setItem(TOKEN_STORAGE_KEY, token)
}

function removeStoredToken() {
  localStorage.removeItem(TOKEN_STORAGE_KEY)
}

function normalizeAccessList(value) {
  if (Array.isArray(value)) {
    return value
      .map((item) => String(item ?? '').trim().toLowerCase())
      .filter(Boolean)
  }

  if (typeof value === 'string' && value.trim() !== '') {
    return value
      .split(',')
      .map((item) => item.trim().toLowerCase())
      .filter(Boolean)
  }

  return []
}

async function fetchAuthenticatedUser(token) {
  const response = await authFetch('/api/v1/me', {
    token,
  })

  if (!response.ok) {
    const error = new Error('Unable to fetch authenticated user')
    error.status = response.status
    throw error
  }

  const payload = await readJsonSafe(response)

  return normalizeSessionUser(payload?.data?.user ?? null)
}

async function sendLogoutRequest(token) {
  try {
    await authFetch('/api/v1/logout', {
      token,
      method: 'POST',
    })
  } catch (error) {
    console.error('Logout request failed:', error)
  }
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => getStoredToken())
  const [user, setUser] = useState(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    let isMounted = true

    async function bootstrapAuth() {
      const storedToken = getStoredToken()

      if (!storedToken) {
        if (isMounted) {
          setToken(null)
          setUser(null)
          setIsLoading(false)
        }
        return
      }

      try {
        const authenticatedUser = await fetchAuthenticatedUser(storedToken)

        if (!isMounted) {
          return
        }

        setToken(storedToken)
        setUser(authenticatedUser)
      } catch (error) {
        if (error?.status !== 401) {
          console.error('Auth bootstrap failed:', error)
        }

        removeStoredToken()

        if (!isMounted) {
          return
        }

        setToken(null)
        setUser(null)
      } finally {
        if (isMounted) {
          setIsLoading(false)
        }
      }
    }

    bootstrapAuth()

    return () => {
      isMounted = false
    }
  }, [])

  const login = (nextToken, nextUser) => {
    setStoredToken(nextToken)
    setToken(nextToken)
    setUser(normalizeSessionUser(nextUser))
    setIsLoading(false)
  }

  const logout = async () => {
    const currentToken = getStoredToken()

    if (currentToken) {
      await sendLogoutRequest(currentToken)
    }

    removeStoredToken()
    setToken(null)
    setUser(null)
    setIsLoading(false)
  }

  const value = useMemo(() => {
    const isAuthenticated = Boolean(token && user)
    const roles = normalizeAccessList(user?.roles)
    const permissions = normalizeAccessList(user?.permissions)
    const roleSet = new Set(roles)
    const permissionSet = new Set(permissions)

    const hasRole = (role) => roleSet.has(String(role ?? '').trim().toLowerCase())
    const hasAnyRole = (requiredRoles) =>
      normalizeAccessList(requiredRoles).some((role) => roleSet.has(role))
    const hasAllRoles = (requiredRoles) =>
      normalizeAccessList(requiredRoles).every((role) => roleSet.has(role))

    const can = (permission) => permissionSet.has(String(permission ?? '').trim().toLowerCase())
    const canAny = (requiredPermissions) =>
      normalizeAccessList(requiredPermissions).some((permission) => permissionSet.has(permission))
    const canAll = (requiredPermissions) =>
      normalizeAccessList(requiredPermissions).every((permission) => permissionSet.has(permission))

    return {
      token,
      user,
      roles,
      permissions,
      isAuthenticated,
      isLoading,
      login,
      logout,
      hasRole,
      hasAnyRole,
      hasAllRoles,
      can,
      canAny,
      canAll,
    }
  }, [token, user, isLoading])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider')
  }

  return context
}
