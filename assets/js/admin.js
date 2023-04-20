import React from 'react'
import { createRoot } from 'react-dom/client'
import { HydraAdmin } from '@api-platform/admin'

const Admin = () => <HydraAdmin entrypoint='http://192.168.60.99/api' />

const container = document.getElementById('root')
const root = createRoot(container)

root.render(<Admin />)
