import tempfile,unittest
from pathlib import Path
from reporte_respaldos_app.app import ServerBackup,summary,generate_docx,generate_pdf
class Tests(unittest.TestCase):
 def test_summary(self):
  r=[ServerBackup("A","hoy"),ServerBackup("B","hoy","x.mdf\ny.ldf")];self.assertEqual({"servers":2,"completed":1,"affected":1,"missing":2},summary(r))
 def test_outputs(self):
  with tempfile.TemporaryDirectory() as f:
   r=[ServerBackup("192.168.2.178 - IFACTURE","14/09/2026 09:47 AM")];d=Path(f)/"r.docx";p=Path(f)/"r.pdf";generate_docx(d,"Reporte","14/09/2026","Sistemas",r,"");generate_pdf(p,"Reporte","14/09/2026","Sistemas",r,"");self.assertGreater(d.stat().st_size,1000);self.assertGreater(p.stat().st_size,1000)
if __name__=="__main__":unittest.main()
