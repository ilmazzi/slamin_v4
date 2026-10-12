import { Navigate } from "react-router-dom"
import { useAuth } from '../app/hooks/useAuth.js'

export default function ProtectedRoute({ children, roles }) {
  const { isAuthenticated, hasAnyRole } = useAuth()

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (Array.isArray(roles) && roles.length > 0 && !hasAnyRole(roles)) {
    return <Navigate to="/dashboard" replace />
  }

  return children
}
