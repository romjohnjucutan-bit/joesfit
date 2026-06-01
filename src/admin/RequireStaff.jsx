import { Navigate } from 'react-router-dom'
import { useAdminAuth } from './AdminAuthContext.jsx'

export default function RequireStaff({ children }) {
  const { staffUser, loading } = useAdminAuth()
  if (loading) {
    return (
      <div style={{ display: 'grid', placeItems: 'center', minHeight: '100vh' }}>
        <div className="loading-spinner" />
      </div>
    )
  }
  if (!staffUser) return <Navigate to="/admin/login" replace />
  return children
}
