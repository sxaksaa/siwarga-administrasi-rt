import { BarChart3, Building2, CreditCard, Gauge, Home, LogOut, Menu, ReceiptText, Users, WalletCards, X } from 'lucide-react'
import { useState } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import api from '../lib/api.js'

const links = [
  { to: '/', label: 'Dashboard', icon: Gauge },
  { to: '/penghuni', label: 'Penghuni', icon: Users },
  { to: '/rumah', label: 'Rumah', icon: Home },
  { to: '/tagihan', label: 'Tagihan', icon: ReceiptText },
  { to: '/pembayaran', label: 'Pembayaran', icon: CreditCard },
  { to: '/pengeluaran', label: 'Pengeluaran', icon: WalletCards },
  { to: '/laporan', label: 'Laporan', icon: BarChart3 },
]

export default function Layout() {
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()
  const location = useLocation()
  const user = JSON.parse(localStorage.getItem('pengguna_administrasi_rt') || '{}')
  const activeLabel = links.find((item) => item.to === location.pathname)?.label || 'Administrasi RT'

  async function logout() {
    try { await api.post('/logout') } catch { /* token tetap dibersihkan */ }
    localStorage.removeItem('token_administrasi_rt')
    localStorage.removeItem('pengguna_administrasi_rt')
    navigate('/login')
  }

  return <div className="app-shell">
    {open && <button className="sidebar-backdrop" aria-label="Tutup navigasi" onClick={() => setOpen(false)} />}
    <aside className={`sidebar ${open ? 'sidebar-open' : ''}`}>
      <div className="brand"><span className="brand-mark"><Building2 size={21} /></span><div><strong>SiWarga</strong><small>Administrasi RT</small></div></div>
      <button className="sidebar-close" onClick={() => setOpen(false)} aria-label="Tutup menu"><X /></button>
      <nav>
        <p className="nav-label">MENU UTAMA</p>
        {links.map(({ to, label, icon: Icon, soon }) => soon
          ? <div className="nav-item nav-disabled" key={to}><Icon size={19} /><span>{label}</span><small>Segera</small></div>
          : <NavLink key={to} to={to} end={to === '/'} onClick={() => setOpen(false)} className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`}><Icon size={19} /><span>{label}</span></NavLink>)}
      </nav>
      <div className="sidebar-user"><div className="avatar">{(user.nama || 'A').slice(0, 1)}</div><div><strong>{user.nama || 'Administrator'}</strong><small>{user.email}</small></div><button onClick={logout} title="Keluar"><LogOut size={18} /></button></div>
    </aside>
    <main className="main-area">
      <header className="topbar"><button className="menu-button" onClick={() => setOpen(true)}><Menu /></button><div><small>Administrasi Perumahan</small><h1>{activeLabel}</h1></div><div className="today">Portal Pengelola</div></header>
      <div className="page-content"><Outlet /></div>
    </main>
  </div>
}
