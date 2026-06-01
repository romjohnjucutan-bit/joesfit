import { Outlet } from 'react-router-dom'
import Header from './Header.jsx'
import Footer from './Footer.jsx'
import CartSidebar from './CartSidebar.jsx'

export default function StorefrontLayout() {
  return (
    <>
      <Header />
      <CartSidebar />
      <main>
        <Outlet />
      </main>
      <Footer />
    </>
  )
}
