import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/auth_state.dart';
import '../core/scan_queue.dart';
import 'screens/exam_list_page.dart';
import 'screens/login_page.dart';
import 'screens/review_page.dart';
import 'screens/scan_page.dart';

void main() {
  final api = ApiClient();
  runApp(
    MultiProvider(
      providers: [
        Provider<ApiClient>.value(value: api),
        Provider<ScanQueue>(create: (_) => ScanQueue()),
        ChangeNotifierProvider<AuthState>(
          create: (_) => AuthState(api)..load(),
        ),
      ],
      child: const OptikApp(),
    ),
  );
}

class OptikApp extends StatelessWidget {
  const OptikApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Optik',
      theme: ThemeData(useMaterial3: true, colorSchemeSeed: Colors.indigo),
      home: const Gate(),
      routes: {
        ExamListPage.route: (_) => const ExamListPage(),
        LoginPage.route: (_) => const LoginPage(),
      },
      onGenerateRoute: (settings) {
        if (settings.name == ScanPage.route) {
          final args = settings.arguments as Map<String, dynamic>;
          return MaterialPageRoute(
            builder: (_) => ScanPage(
              examId: args['examId'] as int,
              booklet: args['booklet'] as String,
            ),
          );
        }
        if (settings.name == ReviewPage.route) {
          final args = settings.arguments as ReviewArgs;
          return MaterialPageRoute(
            builder: (_) => ReviewPage(args: args),
          );
        }
        return null;
      },
    );
  }
}

/// Açılışta token varsa sınav listesi, yoksa giriş ekranı.
class Gate extends StatelessWidget {
  const Gate({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    if (!auth.loaded) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }
    return auth.user != null ? const ExamListPage() : const LoginPage();
  }
}
