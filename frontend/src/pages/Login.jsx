import { Building2, Eye, EyeOff, LockKeyhole, Mail } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../lib/api.js'

export default function Login() {
  const [form, setForm] = useState({ email: 'admin@administrasirt.test', password: 'AdminRT123!' })
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const navigate = useNavigate()

  async function submit(event) {
    event.preventDefault(); setLoading(true); setError('')
    try {
      const { data } = await api.post('/login', form)
      localStorage.setItem('token_administrasi_rt', data.token)
      localStorage.setItem('pengguna_administrasi_rt', JSON.stringify(data.pengguna))
      navigate('/')
    } catch (err) {
      setError(err.response?.data?.message || 'Tidak dapat terhubung ke server.')
    } finally { setLoading(false) }
  }

  return <main className="login-page">
    <section className="login-panel">
      <div className="login-brand"><span className="brand-mark"><Building2 size={23} /></span><div><strong>SiWarga</strong><small>Administrasi RT</small></div></div>
      <div className="login-heading"><span className="eyebrow">PORTAL PENGELOLA</span><h1>Selamat datang kembali</h1><p>Masuk untuk mengelola data warga dan keuangan lingkungan.</p></div>
      {error && <div className="alert error">{error}</div>}
      <form onSubmit={submit} className="form-stack">
        <label>Email<div className="input-icon"><Mail size={18} /><input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required /></div></label>
        <label>Kata sandi<div className="input-icon"><LockKeyhole size={18} /><input type={showPassword ? 'text' : 'password'} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required /><button type="button" onClick={() => setShowPassword(!showPassword)}>{showPassword ? <EyeOff size={18} /> : <Eye size={18} />}</button></div></label>
        <button className="button primary wide" disabled={loading}>{loading ? 'Memproses...' : 'Masuk ke Dashboard'}</button>
      </form>
      <p className="login-footnote">Sistem informasi administrasi lingkungan yang aman dan terpusat.</p>
    </section>
    <section className="login-visual"><div className="visual-copy"><span>KELOLA LEBIH MUDAH</span><h2>Satu ruang untuk lingkungan yang lebih tertata.</h2><p>Data penghuni, riwayat rumah, iuran, dan pengeluaran tersimpan dalam satu sistem.</p></div><div className="visual-grid"><div /><div /><div /><div /></div></section>
  </main>
}
