import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import Index from "./pages/Index";
import NotFound from "./pages/NotFound";

const queryClient = new QueryClient();

// Modalità di sviluppo standalone (porta 5173 di Vite) o compilata (integrata in Yii2)
const isStandalone =
  window.location.port === "5173" || window.location.port === "3000";
const basename = isStandalone ? "" : "/TherapyCRM/calendar";

// Debug info in console
if (isStandalone) {
  console.log("🚀 Running in STANDALONE mode (dev server)");
  console.log("Router basename:", basename || "(empty)");
} else {
  console.log("📦 Running in COMPILED mode (integrated in Yii2)");
  console.log("Router basename:", basename);
}

const App = () => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <Toaster />
      <Sonner />
      <BrowserRouter basename={basename}>
        <Routes>
          <Route path="/" element={<Index />} />
          <Route path="/:id_patient" element={<Index />} />
          <Route path="/therapist/:id_therapist" element={<Index />} />
          {/* ADD ALL CUSTOM ROUTES ABOVE THE CATCH-ALL "*" ROUTE */}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </BrowserRouter>
    </TooltipProvider>
  </QueryClientProvider>
);

export default App;
