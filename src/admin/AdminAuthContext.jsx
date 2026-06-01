import { createContext, useContext, useEffect, useState } from 'react'
import { supabase } from '../lib/supabase.js'
import { useAuth } from '../context/AuthContext.jsx'

const AdminAuthContext = createContext(null)
export const useAdminAuth = () => useContext(AdminAuthContext)

// Wraps the base auth session and resolves the current user's staff role
// (via RLS-protected `staff` row matched by email). Non-staff => staffUser null.
export function AdminAuthProvider({ children }) {
  const { user, loading: authLoading, signIn, signOut } = useAuth()
  const [staffUser, setStaffUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true
    async function load() {
      if (authLoading) return
      if (!user) {
        if (active) { setStaffUser(null); setLoading(false) }
        return
      }
      setLoading(true)
      const { data } = await supabase
        .from('staff')
        .select('name,email,role,is_active')
        .eq('email', user.email)
        .maybeSingle()
      if (active) {
        setStaffUser(data && data.is_active ? data : null)
        setLoading(false)
      }
    }
    load()
    return () => { active = false }
  }, [user, authLoading])

  const isAdmin = staffUser?.role === 'admin'

  return (
    <AdminAuthContext.Provider
      value={{ user, staffUser, isAdmin, loading: loading || authLoading, signIn, signOut }}
    >
      {children}
    </AdminAuthContext.Provider>
  )
}
