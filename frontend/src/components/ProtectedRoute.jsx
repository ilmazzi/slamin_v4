import { Navigate } from "react-router-dom"
import { useAuth } from '../app/hooks/useAuth.js'

export default function ProtectedRoute({ children, roles, accountTypes, redirectTo = '/dashboard' }) {
  const { isAuthenticated, hasAnyRole, user } = useAuth()

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (Array.isArray(roles) && roles.length > 0 && !hasAnyRole(roles)) {
    return <Navigate to={redirectTo} replace />
  }

  if (
    Array.isArray(accountTypes)
    && accountTypes.length > 0
    && !accountTypes.includes(user?.account_type)
  ) {
    return <Navigate to={redirectTo} replace />
  }

  return children
}
